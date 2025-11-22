<?php

namespace eSkyblock;

use pocketmine\player\Player;
use pocketmine\utils\Config;

class PartnerPermissions {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    private function getPlayerData(Player $player): Config {
        return new Config($this->plugin->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml", Config::YAML);
    }

    public function getPermissions(Player $owner, string $partner): array {
        $data = $this->getPlayerData($owner);
        $island = $data->get("island", []);
        $permissions = $island["permissions"][$partner] ?? [
            "place" => false,
            "break" => false,
            "chest" => false
        ];
        return $permissions;
    }

    public function setPermission(Player $owner, string $partner, string $type, bool $value): void {
        $data = $this->getPlayerData($owner);
        $island = $data->get("island", []);
        if(!isset($island["permissions"])) $island["permissions"] = [];
        if(!isset($island["permissions"][$partner])){
            $island["permissions"][$partner] = [
                "place" => false,
                "break" => false,
                "chest" => false
            ];
        }
        $island["permissions"][$partner][$type] = $value;
        $data->set("island", $island);
        $data->save();
    }

    public function togglePermission(Player $owner, string $partner, string $type): void {
        $current = $this->getPermissions($owner, $partner);
        $newValue = !$current[$type];
        $this->setPermission($owner, $partner, $type, $newValue);
        $owner->sendMessage("§a" . $partner . " için " . $type . " izni " . ($newValue ? "§aAçıldı" : "§cKapatıldı"));
    }
}
