<?php

// check if there is a file attached
// check if file is an swf file

define('NUM_DECIMALS', 2);

if(count($_FILES) > 0)
{
    $fields = array('name',
                    'filesize',
                    'framerate',
                    'frames',
                    'duration',
                    'version',
                    'dimensions');

    $labels = array('name'       => 'Analyzed file',
                    'filesize'   => 'Filesize',
                    'framerate'  => 'Framerate',
                    'frames'     => 'Frames',
                    'duration'   => 'Duration (calculated)',
                    'version'    => 'Flash version',
                    'dimensions' => 'Dimensions');

    $units = array( 'name'       => '',
                    'filesize'   => 'kB',
                    'framerate'  => 'fps',
                    'frames'     => '',
                    'duration'   => 'seconds',
                    'version'    => '',
                    'dimensions' => 'px');

    $rules = array( 'framerate'  => 30,
                    'duration'   => 30,
                    'filesize'   => 150);

    $filename = 'tmp/' . $_FILES['swf_file']['name'];
    $tempname = $_FILES['swf_file']['tmp_name'];
    if($_FILES['swf_file']['type'] == 'application/x-shockwave-flash' || $_FILES['swf_file']['type'] == 'application/vnd.adobe.flash.movie')
    {
        move_uploaded_file($tempname, $filename);
        chmod($filename, 0766);

        $swf = analyzeSwf($filename);

        // additional classes depending on the test result
        $classes = array();
        $classes['infobox'] = 'check';

        // verify and evaluate results
        foreach($fields as $property)
        {
            if(isset($rules[$property]))
            {
                $recommendations[$property] = 'recommended: max. ' . $rules[$property] . ' ' . $units[$property];
                if($swf->{$property} >= $rules[$property])
                {
                    $classes[$property] = 'warn';
                    $classes['infobox'] = 'cross';
                }
            }
            else
            {
                $classes[$property] = '';
            }
        }
    }
    else
    {
        $filename = $_FILES['swf_file']['name'];
        $uploadError = true;
    }

}

require_once('views/header.html');
require('views/seperator.html');
require_once('views/upload.html');

if(isset($swf)) {
    // display result
    require('views/seperator.html');
    require_once('views/swf_info.php');
}
else if($uploadError)
{
    require('views/seperator.html');
    require('views/error.html');
}
require_once('views/footer.html');

// end of program
exit(0);













/**
 * analyzeSwf
 *
 * actually kindof a wrapper using the swfHeader-class and modifying the result to reflect our needs
 *
 * @param mixed $filename
 * @access public
 * @return void
 */
function analyzeSwf($filename)
{
    require_once('swfheader.class.php');

    // analyze swf file
    $swf = new swfHeader();
    $swf->getDimensions($filename);
    $swf->framerate = 0;
    foreach($swf->fps as $fps)
    {
        if($fps > $swf->framerate)
        {
            $swf->framerate = $fps;
        }
    }
    // filesize in kb with two decimal places
    $swf->filesize = roundDecimals($swf->size / 1024);

    // duration
    $swf->duration = roundDecimals($swf->frames / $swf->framerate);

    // name without path
    $swf->name = $_FILES['swf_file']['name'];

    // dimensions as one string:
    $swf->dimensions = $swf->width . 'x' . $swf->height;

    return $swf;
}


function roundDecimals($value)
{
    $decMultiplier = pow(10, NUM_DECIMALS);
    $value = (ceil($value * $decMultiplier)) / $decMultiplier;
    return $value;
}


