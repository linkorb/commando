<?php

namespace Commando;

use RuntimeException;
use Commando\ConfigLoader\JsonConfigLoader;
use Commando\ConfigLoader\YamlConfigLoader;
use Commando\Event\HipChatSubscriber;
use Commando\Event\SlackSubscriber;
use Symfony\Component\Yaml\Yaml;
use HipChat\HipChat;

class CommandoFactory
{
    public static function create(string $configFilename = null)
    {
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

        
        // Radvance-based projects shortcut
        if (file_exists('app/config/parameters.yml')) {
            $yaml = file_get_contents('app/config/parameters.yml');
            $config = Yaml::parse($yaml);
            if (!$commando->getJobStore()) {
                $storeClass = "Commando\\JobStore\\PdoJobStore";
                $store = new $storeClass($config['parameters']);
                $commando->setJobStore($store);
            }
            if (isset($config['parameters']['hipchat_token'])) {
                $token = $config['parameters']['hipchat_token'];
                $roomId = $config['parameters']['hipchat_room_id'];
                $mentions = $config['parameters']['hipchat_mentions'];
                $name = $config['parameters']['hipchat_name'];

                $hipChat = new HipChat($token);
                $hipChatSubscriber = new HipChatSubscriber($hipChat, $roomId, $mentions, $name);
                $commando->getDispatcher()->addSubscriber($hipChatSubscriber);
            }
            if (isset($config['parameters']['slack_url'])) {
                $url = $config['parameters']['slack_url'];
                $channel = $config['parameters']['slack_channel'];
                $mentions = $config['parameters']['slack_mentions'];
                $name = $config['parameters']['slack_name'];
                $icon = $config['parameters']['slack_icon'];
                $settings = [
                    'username' => $name,
                    'icon' => $icon,
                    'channel' => '#' . trim($channel, '#'),
                    'link_names' => true
                ];

                $slack = new \Maknz\Slack\Client($url, $settings);
                $slackSubscriber = new SlackSubscriber($slack, $mentions);
                $commando->getDispatcher()->addSubscriber($slackSubscriber);
            }
        }
        return $commando;
    }
}