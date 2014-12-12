<?php

define('__ROOT__', './');

require_once('classes.php');

define('NUM_DECIMALS', 2);

if(count($argv) > 1)
{
    $filepath = $argv[1];
}

$fileAvailable = true;
$uploadError = false;
$formatNotSupported = false;
$outputFormat = '';
$mimetypes = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');


// check if there is a file attached
if(count($_FILES) > 0)
{
    $filename    = $_FILES['media']['name'];
    $filepath    = 'tmp/' . $filename;
    $tempname    = $_FILES['media']['tmp_name'];
    $mimetype    = $_FILES['media']['type'];
    $filesize    = $_FILES['media']['size'];
    $uploadError = $_FILES['media']['error'];

    if($uploadError !== 0)
    {
        // upload errors:
        // 1 - UPLOAD_ERR_INI_SIZE
        // 2 - UPLOAD_ERR_FORM_SIZE
        // 3 -
        echo 'An error occured: ' . $uploadError;
        exit($uploadError);
    }
    $fileAvailable = true;
    $outputFormat = 'HTML';
}
else if(isset($filepath) && $filepath !== '' && file_exists($filepath))
{
    $filename = basename($filepath);
    $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);
    $tempname = $filepath;
    $filesize = filesize($filepath);
    $uploadError = 0;
    $fileAvailable = true;

    $outputFormat = 'plain';
}

// mimetype is only known if we found a valid file
if($fileAvailable)
{
    if(in_array($mimetype, $mimetypes))
    {
        move_uploaded_file($tempname, $filepath);
        chmod($filepath, 0766);

        // TODO: just for now ...
        $implemented = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');

        // flash
        if(in_array($mimetype, $implemented))
        {
            switch($mimetype)
            {
                case 'application/x-shockwave-flash':
                case 'application/vnd.adobe.flash.movie':
                    $fileInfo = new SwfInfo($filepath);
                break;
                case 'image/gif':
                    $fileInfo = new GifInfo($filepath);
                break;
                case 'image/jpeg':
                    $fileInfo = new JpgInfo($filepath);
                break;
                case 'image/png':
                    $fileInfo = new PngInfo($filepath);
                break;
            }
            $fileInfo = $fileInfo->analyze();
        }
        else
        {
            $formatNotSupported = true;
            $uploadError = true;
        }
    }

    else
    {
        $uploadError = true;
    }
}


if($outputFormat === 'HTML')
{
    // generate html output
    require_once('views/header.html');
    require('views/seperator.html');
    require_once('views/upload.html');

    if(isset($fileInfo))
    {
        // display result
        require('views/seperator.html');
        require_once('views/swf_info.php');
    }
    else if(isset($uploadError) && $uploadError)
    {
        require('views/seperator.html');
        require('views/error.html');
    }
    require_once('views/footer.html');
}
else if($outputFormat === 'plain')
{
    if(isset($fileInfo))
    {
        unset($fileInfo->recommendations);
        unset($fileInfo->labels);
        unset($fileInfo->allowedDimensions);
        unset($fileInfo->fields);
        // unset($fileInfo->classes);
        unset($fileInfo->units);
        unset($fileInfo->rules);
        echo JSON_encode($fileInfo);
    }
}

// end of program
exit(0);


function roundDecimals($value)
{
    if(!defined(NUM_DECIMALS))
    {
        define('NUM_DECIMALS', 2);
    }
    $decMultiplier = pow(10, NUM_DECIMALS);
    $value = (ceil($value * $decMultiplier)) / $decMultiplier;
    return $value;
}
