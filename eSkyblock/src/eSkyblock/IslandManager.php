<?php

namespace eSkyblock;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\world\Position;

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

        // EasyEdit komutlarını konsoldan çalıştır → oyuncuya mesaj gitmez
        $console = new ConsoleCommandSender($this->plugin->getServer(), $this->plugin->getServer()->getLanguage());
        $this->plugin->getServer()->dispatchCommand($console, "//load " . $schemName);
        $this->plugin->getServer()->dispatchCommand($console, "//paste");

        // Ada spawn koordinatı (örnek: 100,70,100)
        $spawnPos = new Position(100, 70, 100, $player->getWorld());

        $data->set("island", [
            "created" => time(),
            "partners" => [],
            "lastReset" => 0,
            "xp" => 0,
            "level" => 0,
            "spawn" => [$spawnPos->getX(), $spawnPos->getY(), $spawnPos->getZ()]
        ]);
        $data->save();

        $player->sendMessage("§aAda başarıyla oluşturuldu!");
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

        $console = new ConsoleCommandSender($this->plugin->getServer(), $this->plugin->getServer()->getLanguage());
        $this->plugin->getServer()->dispatchCommand($console, "//load " . $schemName);
        $this->plugin->getServer()->dispatchCommand($console, "//paste");

        $data->set("lastReset", time());
        $data->set("xp", 0);
        $data->set("level", 0);
        $data->save();

        $player->sendMessage("§aAda başarıyla sıfırlandı!");
    }

    public function teleportToIsland(Player $player): void {
        $data = $this->getPlayerData($player);
        $island = $data->get("island", null);
        if($island === null){
            $player->sendMessage("§cHenüz adan yok!");
            return;
        }
        [$x, $y, $z] = $island["spawn"];
        $pos = new Position($x, $y, $z, $player->getWorld());
        $player->teleport($pos);
        $player->sendMessage("§aAdana ışınlandın!");
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
