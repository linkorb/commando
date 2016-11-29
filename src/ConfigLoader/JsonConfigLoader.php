<?php

namespace Commando\ConfigLoader;

class JsonConfigLoader extends ArrayConfigLoader
{
    public function loadFile($filename)
    {
        $json = file_get_contents($filename);
        $config = json_decode($json, true);
        return $this->load($config);
    }
}
