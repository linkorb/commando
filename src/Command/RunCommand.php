<?php

namespace Commando\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use RuntimeException;
use Commando\ConfigLoader\JsonConfigLoader;
use Commando\ConfigLoader\YamlConfigLoader;
use Symfony\Component\Yaml\Yaml;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run commando')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Config file to use'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configFilename = $input->getOption('config');
        if ($configFilename) {
            if (strpos($configFilename, '~/') === 0) {
                $configFilename = getenv("HOME") . substr($configFilename, 1);
            }
        } else {
            $configFilename = getcwd() . '/commando.yml';
        }
        
        if (!file_exists($configFilename)) {
            throw new RuntimeException("Config file not found: " . $configFilename);
        }
        
        $extension = pathinfo($configFilename, PATHINFO_EXTENSION);
        switch ($extension) {
            case "yml":
            case "yaml":
                $configFilenameLoader = new YamlConfigLoader();
                break;
            case "json":
                $configFilenameLoader = new JsonConfigLoader();
                break;
            default:
                throw new RuntimeException("Unsupported config file extension: " . $extension);
        }
        
        $commando = $configFilenameLoader->loadFile($configFilename);
        
        if (!$commando->getJobStore()) {
            // Radvance-based projects shortcut
            if (file_exists('app/config/parameters.yml')) {
                $yaml = file_get_contents('app/config/parameters.yml');
                $config = Yaml::parse($yaml);
                $storeClass = "Commando\\JobStore\\PdoJobStore";
                $store = new $storeClass($config['parameters']);
                $commando->setJobStore($store);
            }
        }
        
        $output->write("Commando: Run (config = $configFilename)\n");
        $commando->run();
    }
}
