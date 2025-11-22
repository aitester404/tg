<?php

namespace Skyblock\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Skyblock\Main;
use jojoe77777\FormAPI\SimpleForm;
use Skyblock\utils\IslandManager;

class AdaCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("ada", "Ada menüsünü açar", "/ada", ["island"]);
        $this->setPermission("skyblock.command.ada");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if(!$sender instanceof Player) {
            $sender->sendMessage("Bu komut sadece oyuncular için!");
            return;
        }

        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            switch ($data) {
                case 0:
                    $player->sendMessage("§aKendi adan oluşturuluyor...");
                    IslandManager::createIsland($player);
                    break;
            }
        });

        $form->setTitle("Ada Menüsü");
        $form->addButton("§aAda Oluştur");

        $sender->sendForm($form);
    }
}
