<?php

namespace App\Adapters\Panel;

use App\Core\Patterns\AdapterManager;

class PanelManager extends AdapterManager{
    
    protected function getKey(): string{
        return 'panel';
    }
}