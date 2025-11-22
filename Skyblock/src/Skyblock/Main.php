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

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("SkyblockFormIsland (safe chunks) aktif!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if($command->getName() === "ada"){
            if($sender instanceof Player){
                $this->openIslandMenu($sender);
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
        $form->addButton("§aAda Oluştur");
        $form->addButton("§bAdana Git");
        $form->addButton("§cAdanı Sil");

        $player->sendForm($form);
    }

    private function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        // 1) Dünya yoksa oluştur (void)
        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "mw create $worldName 0 void");
            $player->sendMessage("§aVoid dünya oluşturuldu: §f$worldName");
        }

        // 2) Dünya yükle
        if(!$wm->isWorldLoaded($worldName)){
            $wm->loadWorld($worldName);
        }

        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: §f$worldName");
            return;
        }

        // Ada konumu ve boyutları (düzenlenebilir)
        $baseX = 0; $baseY = 100; $baseZ = 0;
        $width = 6; $length = 6;

        // 3) Adanın kapsadığı chunk’ları hesapla
        [$minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ] = $this->getChunkBounds($baseX, $baseZ, $width, $length);

        // 4) Chunk’ları generate/load et (sync çağrılar yapılır, hazır olana dek bekleme task'ı planlanır)
        for($cx = $minChunkX; $cx <= $maxChunkX; $cx++){
            for($cz = $minChunkZ; $cz <= $maxChunkZ; $cz++){
                // generateChunk: chunk yoksa oluşturur (void dünyada da gereklidir)
                $world->generateChunk($cx, $cz);
                // loadChunk: RAM’e yükler
                $world->loadChunk($cx, $cz);
            }
        }

        // 5) Chunk’ların hazır olduğunu doğrulayıp adayı inşa etmek için gecikmeli task
        $attempts = 0;
        $maxAttempts = 40; // ~2 saniye (40 * 1 tick)
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($player, $world, $baseX, $baseY, $baseZ, $width, $length, &$attempts, $maxAttempts) : void {
            if(!$player->isOnline()){
                return;
            }

            $ready = $this->areChunksReady($world, $baseX, $baseZ, $width, $length);
            if($ready){
                // 6) Ada inşa (artık crash yok)
                $this->buildStarterIsland($world, $baseX, $baseY, $baseZ, $width, $length);

                // 7) Oyuncuyu ortasına ışınla
                $centerX = $baseX + intdiv($width, 2);
                $centerZ = $baseZ + intdiv($length, 2);
                $player->teleport(new Position($centerX + 0.5, $baseY + 2, $centerZ + 0.5, $world));
                $player->sendMessage("§aAda oluşturuldu: taş platform, sandık, ağaç, su ve lava hazır!");

                // Bu task’i sonlandır
                $this->getScheduler()->cancelTask($this->getScheduler()->getCurrentTask()->getTaskId());
                return;
            }

            $attempts++;
            if($attempts >= $maxAttempts){
                // Zorunlu fallback: chunk'lar hâlâ hazır değilse kullanıcıya bilgi ver ve task'i iptal et
                $player->sendMessage("§cAda chunk'ları zamanında hazırlanamadı. Lütfen tekrar deneyin.");
                $this->getScheduler()->cancelTask($this->getScheduler()->getCurrentTask()->getTaskId());
            }
        }), 1); // her tick kontrol
    }

    private function goIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();
        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }

        // Spawn chunk hazırla
        $spawnX = 3; $spawnY = 102; $spawnZ = 3;
        $world->generateChunk($spawnX >> 4, $spawnZ >> 4);
        $world->loadChunk($spawnX >> 4, $spawnZ >> 4);

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

    private function areChunksReady(\pocketmine\world\World $world, int $baseX, int $baseZ, int $width, int $length) : bool {
        [$minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ] = $this->getChunkBounds($baseX, $baseZ, $width, $length);
        for($cx = $minChunkX; $cx <= $maxChunkX; $cx++){
            for($cz = $minChunkZ; $cz <= $maxChunkZ; $cz++){
                if(!$world->isChunkGenerated($cx, $cz) || !$world->isChunkLoaded($cx, $cz)){
                    return false;
                }
            }
        }
        return true;
    }

    private function buildStarterIsland(\pocketmine\world\World $world, int $baseX, int $baseY, int $baseZ, int $width, int $length) : void {
        // 1) Taş platform
        for($x = 0; $x < $width; $x++){
            for($z = 0; $z < $length; $z++){
                $world->setBlockAt($baseX + $x, $baseY, $baseZ + $z, VanillaBlocks::STONE());
            }
        }

        // 2) Ortada meşale
        $centerX = $baseX + intdiv($width, 2);
        $centerZ = $baseZ + intdiv($length, 2);
        $world->setBlockAt($centerX, $baseY + 1, $centerZ, VanillaBlocks::TORCH());

        // 3) Başlangıç sandığı
        $world->setBlockAt($centerX + 1, $baseY + 1, $centerZ, VanillaBlocks::CHEST());

        // 4) Küçük ağaç (basit gövde + yaprak)
        for($y = 1; $y <= 3; $y++){
            $world->setBlockAt($centerX - 2, $baseY + $y, $centerZ - 2, VanillaBlocks::OAK_LOG());
        }
        for($x = -3; $x <= -1; $x++){
            for($z = -3; $z <= -1; $z++){
                $world->setBlockAt($centerX + $x, $baseY + 4, $centerZ + $z, VanillaBlocks::OAK_LEAVES());
            }
        }

        // 5) Su ve lava
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
