<?php

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

