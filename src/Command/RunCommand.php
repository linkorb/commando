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
            $configFilename = getcwd() . '/commando.json';
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
        
        $output->write("Commando: Run (config = $configFilename)\n");
        $commando->run();
    }
}
