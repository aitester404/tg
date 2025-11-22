<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {

    public function onEnable() : void {
        $this->getLogger()->info("Skyblock plugin aktif!");
        $this->getServer()->getCommandMap()->register("ada", new commands\AdaCommand($this));
    }
}
