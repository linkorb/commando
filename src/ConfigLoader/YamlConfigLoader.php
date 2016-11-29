<?php

namespace Commando\ConfigLoader;

use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends ArrayConfigLoader
{
    public function loadFile($filename)
    {
        $yaml = file_get_contents($filename);
        $config = Yaml::parse($yaml);
        return $this->load($config);
    }
}
