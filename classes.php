<?php

class MediaInfo
{
    public $fields;             // field identifier
    public $labels;             // descriptive names usable as labels
    public $units;              // units like 'frames', 'seconds', 'kB'
    public $rules;              // several max. limits like "30 fps"
    public $classes;            // additional "classed" like 'error', 'warn' etc.
    public $recommendations;    // generated from rules and units

    public $file;

    public function __construct($filepath)
    {
        $this->file = $filepath;
        $this->name = basename($this->file);
        $this->filesize = roundDecimals(filesize($this->file) / 1024);

        $this->fields = array('name',
                              'filesize',
                              'dimensions');

        $this->labels = array('name'       => 'Analyzed file',
                              'filesize'   => 'Filesize',
                              'dimensions' => 'Dimensions');

        $this->units = array( 'name'       => '',
                              'filesize'   => 'kB',
                              'dimensions' => 'px');

        $this->rules = array( 'filesize'   => 150);

        $this->classes = array('infobox' => 'check');

        $connector = new APIConnector();
        $this->allowedDimensions = $connector->getAllowedBannerDimensions();

    }

    public function checkRules()
    {
        /* PART 2: enhance */
        // verify and evaluate results
        foreach($this->fields as $property)
        {
            if(isset($this->rules[$property]))
            {
                $this->recommendations[$property] = 'recommended: max. ' . $this->rules[$property] . ' ' . $this->units[$property];
                if($this->{$property} >= $this->rules[$property])
                {
                    $this->classes[$property] = 'warn';
                    $this->classes['infobox'] = 'cross';
                }
            }
            else
            {
                $this->classes[$property] = '';
            }
        }

        if(!$this->hasValidDimensions())
        {
            $this->classes['dimensions'] = 'warn';
            $this->classes['infobox'] = 'cross';
        }
    }



    /**
     * getHtml
     *
     * return valid html markup to display the file
     * since most (!) media data files will be images, using the html img tag,
     * this is set as default here. Will be overwritten in  derived classes if
     * required
     *
     * @access public
     * @return void
     */
    public function getHtml()
    {
        $markup = '<img src="' . $this->file . '" width="' . $this->width . '" height="' . $this->height . '" alt="" />';
        return $markup;
    }

    public function hasValidDimensions()
    {
        $dimensionsOk = false;
        foreach($this->allowedDimensions AS $dim)
        {
            if($dim->width > 0 && $dim->height > 0)
            {
                $this->recommendations['dimensions'] .= $dim->width . 'x' . $dim->height . ' (' . $dim->name . ")\n";
            }
            if($this->width == $dim->width && $this->height == $dim->height)
            {
                $dimensionsOk = true;
            }
        }
        return $dimensionsOk;
    }
}

class StaticMediaInfo extends MediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);

        list($this->width, $this->height) = getimagesize($this->file);
        $this->dimensions = $this->width . 'x' . $this->height;
    }

    public function analyze()
    {
        $this->checkRules();
        return $this;
    }
}

class AnimatedMediaInfo extends MediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);

        $this->fields[]            = 'framerate';
        $this->labels['framerate'] = 'Framerate';
        $this->units['framerate']  = 'fps';
        $this->rules['framerate']  = 30;

        $this->fields[]         = 'frames';
        $this->labels['frames'] = 'Frames';
        $this->units['frames']  = '';

        $this->fields[]           = 'duration';
        $this->labels['duration'] = 'Duration (calculated)';
        $this->units['duration']  = 'seconds';
        $this->rules['duration']  = 30;

        $this->fields[]           = 'version';
        $this->labels['version'] = 'Version';
    }
}

class SwfInfo extends AnimatedMediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);

        $this->labels['version'] = 'Flash version';
    }

    public function analyze()
    {
        require_once('swfheader.class.php');

        // analyze swf file
        $swf = new swfHeader();
        $swf->getDimensions($this->file);

        $this->framerate = 0;
        foreach($swf->fps as $fps)
        {
            if($fps > $this->framerate)
            {
                $this->framerate = $fps;
            }
        }
        // filesize in kb with two decimal places
        $this->filesize = roundDecimals($swf->size / 1024);
        $this->width    = $swf->width;
        $this->height   = $swf->height;
        $this->version  = $swf->version;
        $this->frames   = $swf->frames;

        // duration
        $this->duration = roundDecimals($swf->frames / $swf->framerate);

        // dimensions as one string:
        $this->dimensions = $swf->width . 'x' . $swf->height;

        $this->checkRules();

        return $this;
    }

    public function getHtml()
    {
        $markup = <<< EOT
        <object id="flash"
                width="$this->width"
                height="$this->height"
                data="$this->file"
                type="application/x-shockwave-flash">
            <param name="src" value="tmp/$this->file">
            <!-- Alternativinhalt -->
        </object>
EOT;

        return $markup;
    }
}

class GifInfo extends AnimatedMediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);
    }

    public function analyze()
    {
        // $image = new Imagick($this->file);
        $info = array();
        exec('identify -format "Frame %s: %Tcs\n" ' . $this->file, $info);

        $duration = 0;
        $avgFramerate = 0;
        $framecount = 0;
        $frameDelays = array();

        // get the average time for each frame
        foreach($info as $frame)
        {
            if($frame != '')
            {
                preg_match("/^Frame \d+: (\d+)cs/", $frame, $frameDelay);
                $curDelay = intval($frameDelay[1], 10);
                $duration += $curDelay;
                $frameDelays[] = $curDelay;
                $framecount++;
            }
        }

        if($framecount === 1)
        {
            $duration = 0;
        }
        else
        {
            $avgFramerate = $duration / $framecount;
        }

        list($this->width, $this->height) = getimagesize($this->file);

        $metadata = array();

        try
        {
            exec('file ' . $this->file, $metadata);
            preg_match("/^.*version (.*),/", $metadata[0], $version);
            $this->version = $version[1];
        }
        catch(Exception $e)
        {
            // file is not executable, display some default output instead of the version.
            // no gamebreaker, though :)
            $this->version = 'n/a';
        }

        $this->frames     = $framecount;
        $this->framerate  = intval(ceil(100/$avgFramerate));
        $this->duration   = roundDecimals($duration / 100);

        if($this->framerate === 0)
        {
            $this->framerate = 'n/a';
            $this->units['framerate'] = '';
            $this->duration = 'n/a';
            $this->units['duration'] = '';
        }
        $this->dimensions = $this->width . 'x' . $this->height;

        $this->checkRules();

        return $this;
    }
}

class JpgInfo extends StaticMediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);
    }
}

class PngInfo extends StaticMediaInfo
{
    public function __construct($filepath)
    {
        parent::__construct($filepath);
    }
}

