<?php

// check if there is a file attached
// check if file is an swf file

define('NUM_DECIMALS', 2);

if(count($_FILES) > 0)
{
    $uploadError = false;
    $mimetypes = array('application/x-shockwave-flash', 'application/vnd.adobe.flash.movie', 'image/gif', 'image/jpeg', 'image/png');

    $filename = $_FILES['media']['name'];
    $filepath = 'tmp/' . $filename;
    $tempname = $_FILES['media']['tmp_name'];
    $mimetype = $_FILES['media']['type'];

    if(in_array($mimetype, $mimetypes))
    {
        move_uploaded_file($tempname, $filepath);
        chmod($filename, 0766);

    }

    // flash
    if($mimetype == 'application/x-shockwave-flash' || $mimetype == 'application/vnd.adobe.flash.movie')
    {
        $swf = analyzeSwf('tmp/' . $filename);

        // additional classes depending on the test result
        $classes = array();
        $recommendations = array();
        $classes['infobox'] = 'check';

        $fileInfo = new SwfInfo();

        // verify and evaluate results
        foreach($fileInfo->fields as $property)
        {
            if(isset($fileInfo->rules[$property]))
            {
                $recommendations[$property] = 'recommended: max. ' . $fileInfo->rules[$property] . ' ' . $fileInfo->units[$property];
                if($swf->{$property} >= $fileInfo->rules[$property])
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
    else if($mimetype == 'image/gif')
    {
        $formatNotSupported = true;
        $uploadError = true;
    }
    else if($mimetype == 'image/jpeg' || $mimetype == 'image/png')
    {
        $formatNotSupported = true;
        $uploadError = true;
    }
    else
    {
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
    $swf->name = $_FILES['media']['name'];

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














class MediaInfo
{
    public $fields;
    public $labels;
    public $units;
    public $rules;

    public function __construct()
    {
        $this->fields = array('name',
                              'filesize',
                              'framerate',
                              'frames',
                              'duration',
                              'version',
                              'dimensions');

        $this->labels = array('name'       => 'Analyzed file',
                              'filesize'   => 'Filesize',
                              'framerate'  => 'Framerate',
                              'frames'     => 'Frames',
                              'duration'   => 'Duration (calculated)',
                              'version'    => 'Flash version',
                              'dimensions' => 'Dimensions');

        $this->units = array( 'name'       => '',
                              'filesize'   => 'kB',
                              'framerate'  => 'fps',
                              'frames'     => '',
                              'duration'   => 'seconds',
                              'version'    => '',
                              'dimensions' => 'px');

        $this->rules = array( 'framerate'  => 30,
                              'duration'   => 30,
                              'filesize'   => 150);
    }
}

class SwfInfo extends MediaInfo
{
    public function __construct()
    {
        parent::__construct();
    }
}

class GifInfo extends MediaInfo
{
    public function __construct()
    {
        parent::__construct();
    }
}

class JpgInfo extends MediaInfo
{
    public function __construct()
    {
        parent::__construct();
    }
}

class PngInfo extends MediaInfo
{
    public function __construct()
    {
        parent::__construct();
    }
}
