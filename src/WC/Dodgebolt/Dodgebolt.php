<?php

declare(strict_types=1);

namespace Dodgebolt\sumo;

use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use WC\Dodgebolt\arena\Arena;
use WC\Dodgebolt\commands\SumoCommand;
use WC\Dodgebolt\math\Vector3;
use WC\Dodgebolt\provider\YamlDataProvider;

/**
 * Class Dodgebolt
 * @package onevsone
 */
class Dodgebolt extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;

    /** @var EmptyArenaChooser $emptyArenaChooser */
    public $emptyArenaChooser;

    /** @var Command[] $commands */
    public $commands = [];

    /** @var Arena[] $arenas */
    public $arenas = [];

    /** @var Arena[] $setters */
    public $setters = [];

    /** @var int[] $setupData */
    public $setupData = [];

    public function onLoad() {
        $this->dataProvider = new YamlDataProvider($this);
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->dataProvider->loadArenas();
        $this->emptyArenaChooser = new EmptyArenaChooser($this);
        $this->getServer()->getCommandMap()->register("db", $this->commands[] = new SumoCommand($this));
    }

    public function onDisable() {
        $this->dataProvider->saveArenas();
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled(\true);
        $args = explode(" ", $event->getMessage());

        /** @var Arena $arena */
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage("§aSumo setup help (1/1):\n".
                "§7help : Displays list of available setup commands\n" .
                "§7level : Set arena level\n".
                "§7slots : Set arena slots\n".
                "§7setspawn : Set arena spawns\n".
                "§7joinsign : Set arena joinsign\n".
                "§7enable : Enable the arena");
                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cSorry the command must be used with: §7slots <int: slots>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§l§6> Slots sucessfully updated to $args[1]!");
                break;
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("§cSorry the command must be used with: §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("§cLevel $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§6Arena level updated to $args[1]!");
                $arena->data["level"] = $args[1];
                break;
            case "setspawn":
                if(!isset($args[1])) {
                    $player->sendMessage("§cSorry the command must be used with: §7setspawn <int: spawn>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§cType number!");
                    break;
                }
                if((int)$args[1] > $arena->data["slots"]) {
                    $player->sendMessage("§cThere are only {$arena->data["slots"]} slots!");
                    break;
                }

                $arena->data["spawns"]["spawn-{$args[1]}"] = (new Vector3($player->getX(), $player->getY(), $player->getZ()))->__toString();
                $player->sendMessage("§a> Spawn $args[1] set to X: " . (string)round($player->getX()) . " Y: " . (string)round($player->getY()) . " Z: " . (string)round($player->getZ()));
                break;
            case "joinsign":
                $player->sendMessage("§aBreak block to set join sign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§6Arena is already enabled!");
                    break;
                }
                if(!$arena->enable()) {
                    $player->sendMessage("§cCould not load arena, there are missing information!");
                    break;
                }
                $player->sendMessage("§aArena enabled!");
                break;
            case "done":
                $player->sendMessage("§aYou are successfully leaved setup mode!");
                unset($this->setters[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§6You are in setup mode.\n".
                    "§7- use §lhelp §r§7to display available commands\n"  .
                    "§7- or §ldone §r§7to leave setup mode");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§aJoin sign updated!");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled(\true);
                    break;
            }
        }
    }

    /**
     * @param Player $player
     */
    public function joinToRandomArena(Player $player) {
        $arena = $this->emptyArenaChooser->getRandomArena();
        if(!is_null($arena)) {
            $arena->joinToArena($player);
            return;
        }
        $player->sendMessage("§cAll the arenas are full! Please wait...");
    }
}
