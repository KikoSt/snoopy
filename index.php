<?php

define('__ROOT__', './');

require_once('classes.php');
require_once('libraries/PHPExcel/PHPExcel.php');

define('NUM_DECIMALS', 2);

$fileAvailable = false;
$uploadError = false;
$formatNotSupported = false;
$outputFormat = 'HTML';
$mimetypes = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');
$filetype = 'all';

if(isset($argv) && count($argv) > 1)
{
    $filepath = $argv[1];
}

$protocol = new PHPExcel();

// check if there is a file attached
if(count($_FILES) > 0)
{
    $files = array();
    $fileInfos = array();

    // first of all, convert the _FILE array to something we can work with
    // initially, there are 5 arrays, containing
    // - all names
    // - all tmp_names
    // - all mimetypes
    // - all filesizes
    // - all upload_errors
    $files = $_FILES['media'];

    $filenames = $files['name'];
    $filetemps = $files['tmp_name'];
    $mimetypes = $files['type'];
    $filesizes = $files['size'];
    $errors    = $files['error'];

    for($i=0; $i<count($filenames); $i++)
    {
        $file = new StdClass();
        $file->filename = $filenames[$i];
        $file->mimetype = $mimetypes[$i];
        $file->tempname = $filetemps[$i];
        $file->filesize = $filesizes[$i];
        $file->uploadError = $errors[$i];

        if($file->uploadError != 0)
        {
            // upload errors:
            // 1 - UPLOAD_ERR_INI_SIZE
            // 2 - UPLOAD_ERR_FORM_SIZE
            // 3 -
            echo 'An error occured: ' . $file->uploadError;
            exit($file->uploadError);
        }
        $fileAvailable = true;
        $outputFormat = 'HTML';

        if(in_array($file->mimetype, $mimetypes))
        {
            $filepath = 'tmp/' . $file->filename;
            move_uploaded_file($file->tempname, $filepath);
            chmod($filepath, 0766);

            // TODO: just for now ...
            $implemented = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');

            // flash
            if(in_array($file->mimetype, $implemented))
            {
                switch($file->mimetype)
                {
                    case 'application/x-shockwave-flash':
                    case 'application/vnd.adobe.flash.movie':
                        $fileInfo = new SwfInfo($filepath);
                        $filetype = 'swf';
                    break;
                    case 'image/gif':
                        $fileInfo = new GifInfo($filepath);
                        $filetype = 'gif';
                    break;
                    case 'image/jpeg':
                        $fileInfo = new JpgInfo($filepath);
                        $filetype = 'jpeg';
                    break;
                    case 'image/png':
                        $fileInfo = new PngInfo($filepath);
                        $filetype = 'png';
                    break;
                }
                $fileInfos[] = $fileInfo->analyze();

                $protocol->getActiveSheet()->SetCellValue('A1', 'filename');
                $protocol->getActiveSheet()->SetCellValue('B1', 'filetype');
                $protocol->getActiveSheet()->SetCellValue('C1', 'dimensions (pixel)');
                $protocol->getActiveSheet()->SetCellValue('D1', 'silesize');
                $protocol->getActiveSheet()->SetCellValue('E1', 'Framerate');
                $protocol->getActiveSheet()->SetCellValue('F1', 'Frames');
                $protocol->getActiveSheet()->SetCellValue('G1', 'Duration');
                $protocol->getActiveSheet()->SetCellValue('H1', 'Version');
                $protocol->getActiveSheet()->SetCellValue('A' . ($i+2), $name);
                $protocol->getActiveSheet()->SetCellValue('B' . ($i+2), $filetype);
                $protocol->getActiveSheet()->SetCellValue('C' . ($i+2), $fileInfo->dimensions);
                $protocol->getActiveSheet()->SetCellValue('D' . ($i+2), $fileInfo->filesize . 'kb');
                $protocol->getActiveSheet()->SetCellValue('E' . ($i+2), $fileInfo->framerate);
                $protocol->getActiveSheet()->SetCellValue('F' . ($i+2), $fileInfo->frames);
                $protocol->getActiveSheet()->SetCellValue('G' . ($i+2), $fileInfo->duration);
                $protocol->getActiveSheet()->SetCellValue('H' . ($i+2), $fileInfo->version);
            }
            else
            {
                $formatNotSupported = true;
                $uploadError = true;
            }
        }

    }


    $objWriter = new PHPExcel_Writer_Excel2007($protocol);
    $objWriter->save('tmp/protocol.xls');


    // generate output
    require_once('views/header.html');
    require('views/seperator.html');
    require_once('views/upload.html');

    foreach($fileInfos AS $fileInfo)
    {
        // display result
        require('views/seperator.html');
        require('views/media_info.php');
    }
//        else if(isset($uploadError) && $uploadError)
//        {
//            require('views/seperator.html');
//            require('views/error.html');
//        }
    require_once('views/footer.html');



//    else if(isset($filepath) && $filepath !== '' && file_exists($filepath))
//    {
//        $filename = basename($filepath);
//        $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);
//        $tempname = $filepath;
//        $filesize = filesize($filepath);
//        $uploadError = 0;
//        $fileAvailable = true;
//        $outputFormat = 'plain';
//    }
//
//    // mimetype is only known if we found a valid file
//    if($fileAvailable)
//    {
//        if(in_array($mimetype, $mimetypes))
//        {
//            move_uploaded_file($tempname, $filepath);
//            chmod($filepath, 0766);
//
//            // TODO: just for now ...
//            $implemented = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');
//
//            // flash
//            if(in_array($mimetype, $implemented))
//            {
//                switch($mimetype)
//                {
//                    case 'application/x-shockwave-flash':
//                    case 'application/vnd.adobe.flash.movie':
//                        $fileInfo = new SwfInfo($filepath);
//                        $filetype = 'swf';
//                    break;
//                    case 'image/gif':
//                        $fileInfo = new GifInfo($filepath);
//                        $filetype = 'gif';
//                    break;
//                    case 'image/jpeg':
//                        $fileInfo = new JpgInfo($filepath);
//                        $filetype = 'jpeg';
//                    break;
//                    case 'image/png':
//                        $fileInfo = new PngInfo($filepath);
//                        $filetype = 'png';
//                    break;
//                }
//                $fileInfo = $fileInfo->analyze();
//            }
//            else
//            {
//                $formatNotSupported = true;
//                $uploadError = true;
//            }
//        }
//
//        else
//        {
//            $uploadError = true;
//        }
//    }
//
//
//    if($outputFormat === 'HTML')
//    {
//        // generate html output
//        require_once('views/header.html');
//        require('views/seperator.html');
//        require_once('views/upload.html');
//
//        if(isset($fileInfo))
//        {
//            // display result
//            require('views/seperator.html');
//            require_once('views/media_info.php');
//        }
//        else if(isset($uploadError) && $uploadError)
//        {
//            require('views/seperator.html');
//            require('views/error.html');
//        }
//        require_once('views/footer.html');
//    }
//    else if($outputFormat === 'plain')
//    {
//        if(isset($fileInfo))
//        {
//            unset($fileInfo->recommendations);
//            unset($fileInfo->labels);
//            unset($fileInfo->allowedDimensions);
//            unset($fileInfo->fields);
//            // unset($fileInfo->classes);
//            unset($fileInfo->units);
//            unset($fileInfo->rules);
//            echo JSON_encode($fileInfo);
//        }
//    }
}
else
{
require_once('views/header.html');
require('views/seperator.html');
require_once('views/upload.html');
require_once('views/footer.html');
}


// end of program
exit(0);


function roundDecimals($value)
{
    if(!defined('NUM_DECIMALS'))
    {
        define('NUM_DECIMALS', 2);
    }
    $decMultiplier = pow(10, NUM_DECIMALS);
    $value = (ceil($value * $decMultiplier)) / $decMultiplier;
    return $value;
}
