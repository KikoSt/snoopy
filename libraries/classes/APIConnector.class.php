<?php
require_once(__ROOT__ . 'config/apiconfig.inc.php');

/**
 * Handles the API connections to the bidder
 *
 * PHP Version 5.5
 *
 * @category    Chameleon
 * @author      Christoph Starkmann <christoph.starkmann@mediadecision.com>
 * @author      Thomas Hummel <thomas.hummel@mediadecision.com
 * @license     Proprietary/Closed Source
 * @copyright   2014 Media Decision GmbH
 */
class APIConnector
{
    private $serviceCalls;

    private $auditUserId;
    private $advertiserId;
    private $companyId;

    public function __construct()
    {
        $this->serviceCalls = array();
        $this->serviceCalls['getTemplates']          = 'advertiser/{advertiserId}/bannerTemplates';
        $this->serviceCalls['getTemplatesByGroup']   = 'advertiser/{advertiserId}/group/{groupId}/bannerTemplates';
        $this->serviceCalls['postTemplate']          = 'bannerTemplate';
        $this->serviceCalls['deleteTemplate']        = 'bannerTemplate/{templateId}';
        $this->serviceCalls['getTemplateById']       = 'bannerTemplate/{templateId}';
        $this->serviceCalls['getProductsByCategory'] = 'company/{companyId}/category/{categoryId}/products';
        $this->serviceCalls['sendCreative']          = 'creativeImage';
        $this->serviceCalls['getEnums']              = 'enums';
        $this->serviceCalls['getCategories']         = 'company/{companyId}/categories';
        $this->serviceCalls['getSubscribedCategories'] = 'bannerTemplate/{idBannerTemplate}/subscribedCategories';
        $this->serviceCalls['postTemplateQuery']     = 'query/bannerTemplates';
        $this->serviceCalls['getProductDataSamples'] = 'query/products';
        $this->serviceCalls['getProductDataSamplesByProductId'] = 'product/{productId}';
    }

    /**
     * getMethodList
     *
     * @access public
     * @return methodList a list containing all currently available REST calls
     */
    public function getMethodList()
    {
        $methodList = array_keys($this->serviceCalls);
        return $methodList;
    }


    /**
     * get
     *
     * @param $path
     * @return string
     */
    public function get($path)
    {
        $restCall = $path;
        $response = file_get_contents($restCall);
        return $response;
    }


    /**
     * getUserStatusValues
     *
     * get all possible user status values via REST API
     * (currently: ACTIVE, PAUSED, DELETED)
     *
     * @access public
     * @return $userStatusValues a list containing all defined user status values
     */
    public function getUserStatusValues()
    {
        $enums = $this->getEnums();
        $userStatusValues = $enums->userStatusValues;
        return $userStatusValues;
    }

    /**
     * getAllowedBannerDimensions
     *
     * get the allowed banner dimensions via REST API
     *
     * @access public
     * @return $userStatusValues a list containing all defined user status values
     */
    public function getAllowedBannerDimensions()
    {
        $enums = $this->getEnums();
        $allowedDimensions = $enums->imageDimensions;
        return $allowedDimensions;
    }


    /**
     * getEnums
     *
     * method to retrieve ALL enums from REST API. While currently there are only the userStatusValues,
     * there will most likely more than that later
     *
     * @access private
     * @return $enums list of ALL enums returned by REST API call
     */
    private function getEnums()
    {
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['getEnums'];
        $curl = $this->getCurl($resource, 'GET');
        $curlResponse = curl_exec($curl);

        $info = curl_getinfo($curl);

        if($info['http_code'] != 204 && $info['http_code'] != 200)
        {
//            $logfile = fopen('log.txt', 'w');
//            fwrite($logfile, $curlResponse . "\n");
//            fclose($logfile);
        }
        curl_close($curl);
        $enums = json_decode($curlResponse);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        return $enums;
    }


    /**
     * sendCreatives
     *
     * send a list of creatives via REST API including the files themselves as base64 encoded binaries
     *
     * @param mixed $creatives
     * @param mixed $feedId
     * @param mixed $categoryId
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function sendCreatives($creatives, $feedId, $categoryId, $groupId=null)
    {
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['sendCreative'];
        $curl = $this->getCurl($resource, 'POST');

        $param = new StdClass();
        $param->creativeImageModels = $creatives;
        $param->idAdvertiser        = $this->getAdvertiserId();
        $param->idAuditUser         = $this->getAuditUserId();
        $param->idCategory          = $categoryId;
        $param->idFeed              = $feedId;
        $param->idGroup             = $groupId;

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        // $curl_response = curl_exec($curl);
        $curlResponse = curl_exec($curl);

        $info = curl_getinfo($curl);

        if($info['http_code'] != 204)
        {
            $logfile = fopen('log.txt', 'w');
            fwrite($logfile, $curlResponse . "\n" . json_encode($param));
            fclose($logfile);
        }

        curl_close($curl);
    }


    /**
     * sendCreative
     *
     * simple wrapper for sending only one creative
     *
     * @param mixed $creative
     * @param mixed $feedId
     * @param mixed $categoryId
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function sendCreative($creative, $feedId, $categoryId, $groupId=null)
    {
        $this->sendCreatives(array($creative), $feedId, $categoryId, $groupId);
    }


    /**
     * getProductsByCategory
     *
     * returns all products for a given category for the currently set company and advertiser
     *
     * @param mixed $categoryId
     * @access public
     * @return void
     */
    public function getProductsByCategory($categoryId)
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{categoryId}', $categoryId, $this->serviceCalls['getProductsByCategory']);
        $resource = str_replace('{companyId}', $this->companyId, $resource);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        $productList = json_decode($curlResponse)->products;

        $products = array();

        foreach($productList AS $product)
        {
            $curProduct = $this->populateProduct($product);
            if($curProduct)
            {
                $products[] = $curProduct;
            }
        }

        return $products;

    }


    private function validateResponse($response)
    {
        if(!is_array($response) && !is_object($response))
        {
            if(strpos($response, 'Error'))
            {
                $response = ltrim(preg_replace ('/<[^>]*>/', ' ', $response));
                return array('valid' => false, 'message' => $response);
           }
        }
        return array('valid' => true, 'message' => '');
    }

    /**
     * Returns the categories depending on the company id
     *
     * TODO Currently the categories are delivered sorted by name ASC
     *
     * @return array
     * @throws Exception
     */
    public function getCategories()
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{companyId}', $this->companyId, $this->serviceCalls['getCategories']);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        $categories = json_decode($curlResponse)->categories;
        $categoriesProcessed = array();

        foreach($categories AS $category)
        {
            $curCategory           = new StdClass();
            $curCategory->id       = $category->idCategory;
            $curCategory->status   = $category->idStatusType;
            $curCategory->name     = $category->categoryName;
            $curCategory->url      = $category->categoryUrl;
            $curCategory->number   = $category->categoryNumber;
            $categoriesProcessed[] = $curCategory;
        }

        return $categoriesProcessed;
    }

    /**
     * getSubscribedCategoriesByTemplateId
     *
     * @param $templateId
     * @return mixed
     * @throws Exception
     */
    public function getSubscribedCategoriesByTemplateId($templateId)
    {
        $resource = REST_API_SERVICE_URL . '/' . 'bannerTemplate/'.$templateId.'/subscribedCategories';
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        $subscribedCategories = json_decode($curlResponse)->categories;

        return $subscribedCategories;
    }



    /**
     * getNumTemplates
     *
     * @return int
     */
    public function getNumTemplates()
    {
        $templateCount = count($this->getTemplates());
        return $templateCount;
    }


    /**
     * getTemplatesByGroup
     *
     *
     *
     * @param mixed $groupId
     * @access public
     * @return void
     */
    public function getTemplatesByGroupId($groupId)
    {
        return $this->getTemplates($groupId);
    }


    /**
     * getTemplates
     *
     * @return array
     * @throws Exception
     */
    public function getTemplates($groupId=null)
    {
        if(!isset($this->advertiserId))
        {
            throw new Exception('advertiserId not set');
        }

        if(null === $groupId)
        {
            $resource = REST_API_SERVICE_URL . '/' . str_replace('{advertiserId}', $this->advertiserId, $this->serviceCalls['getTemplates']);
        }
        else
        {
            $call = $this->serviceCalls['getTemplatesByGroup'];
            $call = str_replace('{advertiserId}', $this->advertiserId, $call);
            $call = str_replace('{groupId}', $groupId, $call);
            $resource = REST_API_SERVICE_URL . '/' . $call;
        }
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('Error trying to get templates');
        }

        curl_close($curl);

        $templateList = json_decode($curlResponse)->bannerTemplateModels;

        $templates = array();

        foreach($templateList AS $template)
        {
            $templates[] = $this->populateBannerTemplate($template);
        }

        return $templates;
    }


    public function getTemplateById($templateId)
    {
        if(!isset($templateId))
        {
            throw new Exception('bannerTemplateId not set');
        }

        $resource = REST_API_SERVICE_URL . '/' . str_replace('{templateId}', $templateId, $this->serviceCalls['getTemplateById']);
        $curl = $this->getCurl($resource, 'GET');

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        return $this->populateBannerTemplate(json_decode($curlResponse));
    }

    /**
     * @param $template
     * @return mixed
     */
    public function sendBannerTemplate(BannerTemplateModel $template)
    {
        $template = json_encode($template->jsonSerialize());
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['postTemplate'];
        $curl = $this->getCurl($resource, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $template);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        return $curlResponse;
    }

    public function sendBannerTemplateQuery(BannerTemplateQuery $bannerTemplateQuery)
    {
        $bannerTemplateQuery = json_encode($bannerTemplateQuery->jsonSerialize());
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['postTemplateQuery'];
        $curl = $this->getCurl($resource, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bannerTemplateQuery);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        return $curlResponse;
    }

    /**
     * Clones the given template
     *
     * For cloning the parent template id is set to the former template id and the template id is set to NULL
     *
     * @param BannerTemplateModel $template
     * @return mixed
     */
    public function cloneBannerTemplate(BannerTemplateModel $template)
    {
        $template->setParentBannerTemplateId($template->getBannerTemplateId());
        $template->setName("COPY OF ". $template->getName());
        $template->setBannerTemplateId(NULL);
        $response = $this->sendBannerTemplate($template);

        return $response;
    }

    /**
     * @param $templateId
     * @return mixed
     */
    public function deleteBannerTemplate($templateId)
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{templateId}', $templateId, $this->serviceCalls['deleteTemplate']);
        $curl = $this->getCurl($resource, 'DELETE');

        $curlResponse = curl_exec($curl);
        curl_close($curl);
        return $curlResponse;
    }


    public function getProductDataSamples($categoryIds, $numSamples)
    {
        $productQueryData = new StdClass();
        if(is_array($categoryIds))
        {
            $productQueryData->categoryIds = $categoryIds;
        }
        $productQueryData->productsPerCategory = (int)$numSamples;
        $productQueryData = json_encode($productQueryData);
        $resource = REST_API_SERVICE_URL . '/' . $this->serviceCalls['getProductDataSamples'];
        $curl = $this->getCurl($resource, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $productQueryData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $productList = json_decode($curlResponse)->products;
        $products = array();

        foreach($productList AS $product)
        {
            $products[] = $this->populateProduct($product);
        }

        return $products;
    }

    /**
     * @param $productId
     * @return ProductModel
     * @throws Exception
     */
    public function getProductDataByProductId($productId)
    {
        $resource = REST_API_SERVICE_URL . '/' . str_replace('{productId}', $productId,
                $this->serviceCalls['getProductDataSamplesByProductId']);
        $curl = $this->getCurl($resource, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $curlResponse = curl_exec($curl);

        curl_close($curl);

        $result = $this->validateResponse($curlResponse);
        if(!$result['valid'])
        {
            throw new Exception('An error occured: ' . $result['message']);
        }

        $productData = $this->populateProduct(json_decode($curlResponse));

        return $productData;
    }

    /**
     * @param $serviceUrl
     * @param $method
     * @return resource
     */
    private function getCurl($serviceUrl, $method)
    {
        $curl = curl_init($serviceUrl);
        $baseAuthUserPwd = (REST_API_USERNAME . ':' . REST_API_PASSWORD);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, $baseAuthUserPwd);

        //todo remove after testing
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if($method === 'GET')
        {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        else if($method === 'PUT')
        {
            curl_setopt($curl, CURLOPT_PUT, true);
        }
        else if ($method === 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        else if ($method === 'DELETE')
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        return $curl;
    }

    /**
     * populateBannerTemplate
     *
     * @param mixed $template
     * @access private
     * @return void
     */
    private function populateBannerTemplate($template)
    {
        $bannerTemplateModel = new BannerTemplateModel();
        $bannerTemplateModel->setAdvertiserId($this->advertiserId);
        $bannerTemplateModel->setAuditUserId((int) $template->idAuditUser);
        $bannerTemplateModel->setDescription((string) $template->description);
        $bannerTemplateModel->setName((string) $template->name);
        $bannerTemplateModel->setBannerTemplateId((int) $template->idBannerTemplate);

        // idParentBanner can be null and php casts null to 0!
        $bannerTemplateModel->setParentBannerTemplateId($template->idParentBannerTemplate);
        $bannerTemplateModel->setSvgContent($template->svgContent);
        $bannerTemplateModel->setDimX((int) $template->dimX);
        $bannerTemplateModel->setDimY((int) $template->dimY);
        $bannerTemplateModel->setGroupId((int) $template->idGroup);
        $bannerTemplateModel->setDateCreate($template->dateCreate);
        $bannerTemplateModel->setDateModified($template->dateModified);
        $bannerTemplateModel->setCategorySubscriptions($template->categorySubscriptions);

        return $bannerTemplateModel;
    }

    /**
     * @param $product
     * @return ProductModel
     */
    private function populateProduct($product)
    {
        // absolutely no point in trying to render banners for products without product images
        if(!isset($product->productUrlImage))
        {
            return false;
        }

        $productModel = new ProductModel();

        $productModel->setProductId($product->idProduct);
        $productModel->setFeedId($product->idFeed);
        $productModel->setCategoryId($product->idCategory);
        $productModel->setCurrencyId($product->idCurrency);

        $productModel->setEan($product->productNumberIsbn);
        $productModel->setIsbn($product->productNumberEan);

        $productModel->setName($product->productName);
        $productModel->setProductUrl($product->productUrl);
        $productModel->setImageUrl($product->productUrlImage);
        $productModel->setDescription($product->productDescription);
        $productModel->setPrice($product->productPrice);
        $productModel->setPriceOld($product->productPriceOld);

        $productModel->setAggregationNumber($product->productNumberAggregation);

        $productModel->setShipping($product->productPriceShipping);
        $productModel->setPromotionStartDate($product->datePromotionStart);
        $productModel->setPromotionEndDate($product->datePromotionEnd);

        $productModel->setProductSize($product->productPropertySize);
        // $productModel->setGender($product->idGender);
        $productModel->setColor($product->productPropertyColour);

        return $productModel;
    }

    /**
     * Get advertiserId.
     *
     * @return advertiserId.
     */
    public function getAdvertiserId()
    {
        return $this->advertiserId;
    }

    /**
     * Set advertiserId.
     *
     * @param advertiserId the value to set.
     */
    public function setAdvertiserId($advertiserId)
    {
        $this->advertiserId = $advertiserId;
    }

    /**
     * Get companyId.
     *
     * @return companyId.
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * Set companyId.
     *
     * @param companyId the value to set.
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
    }



    /**
     * Get auditUserId.
     *
     * @return auditUserId.
     */
    public function getAuditUserId()
    {
        if(!isset($this->auditUserId))
        {
            throw new Exception('AuditUserId not provided!');
        }
        else
        {
            return $this->auditUserId;
        }
    }

    /**
     * Set auditUserId.
     *
     * @param auditUserId the value to set.
     */
    public function setAuditUserId($auditUserId)
    {
        $this->auditUserId = $auditUserId;
    }
}

