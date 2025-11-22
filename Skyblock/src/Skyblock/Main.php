<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use Skyblock\commands\AdaCommand;

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("Skyblock plugin aktif!");
        // Komutu register et
        $this->getServer()->getCommandMap()->register("ada", new AdaCommand($this));
    }
}
