<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("Skyblock plugin aktif!");
    }
}
