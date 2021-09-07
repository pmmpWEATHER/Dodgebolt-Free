<?php

declare(strict_types=1);

namespace jack\sumo\provider;

use pocketmine\level\Level;
use pocketmine\utils\Config;
use jack\sumo\arena\Arena;
use jack\sumo\Sumo;

/**
 * Class YamlDataProvider
 * @package onevsone\provider
 */
class YamlDataProvider {

    /** @var OneVsOne $plugin */
    private $plugin;

    /** @var array $config */
    public $config;

    /**
     * YamlDataProvider constructor.
     * @param OneVsOne $plugin
     */
    public function __construct(Sumo $plugin) {
        $this->plugin = $plugin;
        $this->init();
    }

    public function init() {
        if(!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }
        if(!is_dir($this->getDataFolder() . "arenas")) {
            @mkdir($this->getDataFolder() . "arenas");
        }
        if(!is_dir($this->getDataFolder() . "saves")) {
            @mkdir($this->getDataFolder() . "saves");
        }
    }

    public function loadArenas() {
        foreach (glob($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . "*.yml") as $arenaFile) {
            $config = new Config($arenaFile, Config::YAML);
            $this->plugin->arenas[basename($arenaFile, ".yml")] = new Arena($this->plugin, $config->getAll(\false));
        }
    }

    public function saveArenas() {
        foreach ($this->plugin->arenas as $fileName => $arena) {
            $config = new Config($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $fileName . ".yml", Config::YAML);
            $config->setAll($arena->data);
            $config->save();
        }
    }

    /**
     * @return string $dataFolder
     */
    private function getDataFolder(): string {
        return $this->plugin->getDataFolder();
    }
}
