<?php

declare(strict_types=1);

namespace taqdees\Skyblock\traits;

use taqdees\Skyblock\Main;

trait PluginOwned {
    
    protected Main $plugin;
    
    public function getPlugin(): Main {
        return $this->plugin;
    }
    
    public function setPlugin(Main $plugin): void {
        $this->plugin = $plugin;
    }
}