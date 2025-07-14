<?php

namespace App\Adapters\Panel;

use App\Core\Patterns\AdapterManager;

class PanelManager extends AdapterManager{
    
    protected function config($adapter){
        return parent::config($adapter);
    }
    protected function getKey(): string{
        return 'panel';
    }
}