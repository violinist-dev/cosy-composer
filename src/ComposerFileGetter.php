<?php

namespace eiriksm\CosyComposer;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

class ComposerFileGetter {

    /**
     * @var Filesystem
     */
    protected $fs;

    public function __construct(AdapterInterface $adapter)
    {
        $this->fs = new Filesystem($adapter);
    }

    public function hasComposerFile()
    {
        return $this->fs->has('composer.json');
    }

    public function getComposerJsonData()
    {
        $data = $this->fs->read('composer.json');
        if (false == $data) {
            return FALSE;
        }
        $json = @json_decode($data);
        if (false == $json) {
            return FALSE;
        }
        return $json;
    }
}