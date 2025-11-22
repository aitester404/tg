<?php

namespace eSkyblock;

use pocketmine\player\Player;
use pocketmine\utils\Config;

class IslandManager {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function getPlayerData(Player $player): Config {
        return new Config($this->plugin->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml", Config::YAML);
    }

    public function createIsland(Player $player): void {
        $data = $this->getPlayerData($player);
        if($data->exists("island")){
            $player->sendMessage("§cZaten bir adan var!");
            return;
        }

        $schemFile = $this->plugin->getConfig()->get("startingIsland"); // örn: "start.schem"
        $schemName = pathinfo($schemFile, PATHINFO_FILENAME);

        // EasyEdit komutları
        $player->getServer()->dispatchCommand($player, "//load " . $schemName);
        $player->getServer()->dispatchCommand($player, "//paste");

        $data->set("island", [
            "created" => time(),
            "partners" => [],
            "lastReset" => 0,
            "xp" => 0,
            "level" => 0
        ]);
        $data->save();

        $player->sendMessage("§aAda başarıyla oluşturuldu ve " . $schemName . " yüklendi!");
    }

    public function resetIsland(Player $player): void {
        $data = $this->getPlayerData($player);
        $lastReset = $data->get("lastReset", 0);

        if(time() - $lastReset < 604800){ // 7 gün
            $player->sendMessage("§cAda sadece 7 günde bir sıfırlanabilir!");
            return;
        }

        $schemFile = $this->plugin->getConfig()->get("startingIsland");
        $schemName = pathinfo($schemFile, PATHINFO_FILENAME);

        $player->getServer()->dispatchCommand($player, "//load " . $schemName);
        $player->getServer()->dispatchCommand($player, "//paste");

        $data->set("lastReset", time());
        $data->set("xp", 0);
        $data->set("level", 0);
        $data->save();

        $player->sendMessage("§aAda başarıyla sıfırlandı ve " . $schemName . " yeniden yüklendi!");
    }

    public function addPartner(Player $owner, string $partnerName): void {
        $data = $this->getPlayerData($owner);
        $partners = $data->get("partners", []);

        if(count($partners) >= 2){
            $owner->sendMessage("§cMaksimum 2 ortak ekleyebilirsin!");
            return;
        }

        if(in_array($partnerName, $partners)){
            $owner->sendMessage("§cBu oyuncu zaten ortak!");
            return;
        }

        $partners[] = $partnerName;
        $data->set("partners", $partners);
        $data->save();

        $owner->sendMessage("§a" . $partnerName . " adana ortak olarak eklendi!");
    }

    public function removePartner(Player $owner, string $partnerName): void {
        $data = $this->getPlayerData($owner);
        $partners = $data->get("partners", []);

        if(!in_array($partnerName, $partners)){
            $owner->sendMessage("§cBu oyuncu ortak değil!");
            return;
        }

        $partners = array_diff($partners, [$partnerName]);
        $data->set("partners", $partners);
        $data->save();

        $owner->sendMessage("§a" . $partnerName . " ortaklıktan çıkarıldı!");
    }
}
