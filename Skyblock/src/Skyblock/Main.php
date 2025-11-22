<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\console\ConsoleCommandSender;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("SkyblockFormIsland (ikonlu, PM5 safe chunks) aktif!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if($command->getName() === "ada"){
            if($sender instanceof Player){
                $this->openIslandMenu($sender);
            }else{
                $sender->sendMessage("§cBu komut sadece oyuncular için.");
            }
            return true;
        }
        return false;
    }

    public function openIslandMenu(Player $player) : void {
        $form = new SimpleForm(function(Player $player, ?int $data){
            if($data === null) return;
            switch($data){
                case 0: $this->createIsland($player); break;
                case 1: $this->goIsland($player); break;
                case 2: $this->deleteIsland($player); break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönetmek için bir seçenek seç:");
        // İkonlu butonlar (type: 1 = oyun içi texture)
        $form->addButton("§aAda Oluştur", 1, "textures/blocks/stone");
        $form->addButton("§bAdana Git", 1, "textures/items/ender_pearl");
        $form->addButton("§cAdanı Sil", 1, "textures/items/tnt");

        $player->sendForm($form);
    }

    private function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        // Dünya yoksa oluştur (void)
        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "mw create $worldName 0 void");
            $player->sendMessage("§aVoid dünya oluşturuldu: §f$worldName");
        }

        // Dünya yükle
        if(!$wm->isWorldLoaded($worldName)){
            $wm->loadWorld($worldName);
        }

        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: §f$worldName");
            return;
        }

        // Ada konumu ve boyutları
        $baseX = 0; $baseY = 100; $baseZ = 0;
        $width = 6; $length = 6;

        // Ada chunk sınırları
        [$minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ] = $this->getChunkBounds($baseX, $baseZ, $width, $length);

        // İlgili tüm chunk’ları oluştur + yükle (PM5: loadChunk($x,$z,true) varsa create eder)
        for($cx = $minChunkX; $cx <= $maxChunkX; $cx++){
            for($cz = $minChunkZ; $cz <= $maxChunkZ; $cz++){
                $world->loadChunk($cx, $cz, true); // true: chunk yoksa oluştur
            }
        }

        // Oyuncuyu geçici olarak bölgeye al (client chunk yüklemeyi tetikler)
        $player->teleport(new Position($baseX + 0.5, $baseY + 12, $baseZ + 0.5, $world));
        $player->sendMessage("§7Ada bölgesi hazırlanıyor...");

        // Hazır kontrolü ve inşa (repeating task)
        $attempts = 0;
        $maxAttempts = 100; // ~5 saniye
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($player, $world, $baseX, $baseY, $baseZ, $width, $length, $minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ, &$attempts, $maxAttempts) : void {
            if(!$player->isOnline()){
                $this->getScheduler()->cancelTask($this->getScheduler()->getCurrentTask()->getTaskId());
                return;
            }

            if($this->chunksReady($world, $minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ)){
                // Ada inşa (artık güvenli)
                $this->buildStarterIsland($world, $baseX, $baseY, $baseZ, $width, $length);

                // Ortaya ışınla
                $centerX = $baseX + intdiv($width, 2);
                $centerZ = $baseZ + intdiv($length, 2);
                $player->teleport(new Position($centerX + 0.5, $baseY + 2, $centerZ + 0.5, $world));
                $player->sendMessage("§aAda oluşturuldu: taş platform, sandık, ağaç, su ve lava hazır!");

                $this->getScheduler()->cancelTask($this->getScheduler()->getCurrentTask()->getTaskId());
                return;
            }

            $attempts++;
            if($attempts >= $maxAttempts){
                $player->sendMessage("§cAda chunk'ları zamanında hazırlanamadı. Lütfen tekrar deneyin.");
                $this->getScheduler()->cancelTask($this->getScheduler()->getCurrentTask()->getTaskId());
            }
        }), 1);
    }

    private function goIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();
        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }
        $spawnX = 3; $spawnY = 102; $spawnZ = 3;

        // Spawn chunk’ı oluştur + yükle
        $world->loadChunk($spawnX >> 4, $spawnZ >> 4, true);

        $player->teleport(new Position($spawnX + 0.5, $spawnY, $spawnZ + 0.5, $world));
        $player->sendMessage("§bAdana ışınlandın!");
    }

    private function deleteIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        if($wm->isWorldLoaded($worldName)){
            $wm->unloadWorld($wm->getWorldByName($worldName));
        }

        $path = Server::getInstance()->getDataPath() . "worlds/" . $worldName;
        if(is_dir($path)){
            $this->deleteDirectory($path);
            $player->sendMessage("§cAdan silindi: §f$worldName");
        } else {
            $player->sendMessage("§cAdan bulunamadı!");
        }
    }

    // ——— yardımcı fonksiyonlar ———

    private function getChunkBounds(int $baseX, int $baseZ, int $width, int $length) : array {
        $minX = $baseX;
        $maxX = $baseX + $width - 1;
        $minZ = $baseZ;
        $maxZ = $baseZ + $length - 1;

        $minChunkX = $minX >> 4;
        $maxChunkX = $maxX >> 4;
        $minChunkZ = $minZ >> 4;
        $maxChunkZ = $maxZ >> 4;
        return [$minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ];
    }

    private function chunksReady(World $world, int $minChunkX, int $maxChunkX, int $minChunkZ, int $maxChunkZ) : bool {
        for($cx = $minChunkX; $cx <= $maxChunkX; $cx++){
            for($cz = $minChunkZ; $cz <= $maxChunkZ; $cz++){
                // PM5’te setBlockAt için ikisi de true olmalı
                if(!$world->isChunkGenerated($cx, $cz) || !$world->isChunkLoaded($cx, $cz)){
                    return false;
                }
            }
        }
        return true;
    }

    private function buildStarterIsland(World $world, int $baseX, int $baseY, int $baseZ, int $width, int $length) : void {
        // Taş platform
        for($x = 0; $x < $width; $x++){
            for($z = 0; $z < $length; $z++){
                $world->setBlockAt($baseX + $x, $baseY, $baseZ + $z, VanillaBlocks::STONE());
            }
        }

        // Ortada meşale
        $centerX = $baseX + intdiv($width, 2);
        $centerZ = $baseZ + intdiv($length, 2);
        $world->setBlockAt($centerX, $baseY + 1, $centerZ, VanillaBlocks::TORCH());

        // Başlangıç sandığı
        $world->setBlockAt($centerX + 1, $baseY + 1, $centerZ, VanillaBlocks::CHEST());

        // Küçük ağaç
        for($y = 1; $y <= 3; $y++){
            $world->setBlockAt($centerX - 2, $baseY + $y, $centerZ - 2, VanillaBlocks::OAK_LOG());
        }
        for($x = -3; $x <= -1; $x++){
            for($z = -3; $z <= -1; $z++){
                $world->setBlockAt($centerX + $x, $baseY + 4, $centerZ + $z, VanillaBlocks::OAK_LEAVES());
            }
        }

        // Su ve lava
        $world->setBlockAt($centerX - 2, $baseY + 1, $centerZ + 2, VanillaBlocks::WATER());
        $world->setBlockAt($centerX + 2, $baseY + 1, $centerZ - 2, VanillaBlocks::LAVA());
    }

    private function deleteDirectory(string $dir) : void {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach($files as $file){
            $path = "$dir/$file";
            if(is_dir($path)){
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
