<?php

namespace Commando\JobLoader;

class JsonJobLoader extends ArrayJobLoader implements JobLoaderInterface
{
    public function loadFile($filename)
    {
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        return $this->load($data);
    }
}
