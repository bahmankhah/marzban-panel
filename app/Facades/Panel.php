<?php

namespace App\Facades;

use App\Adapters\Panel\PanelManager;
use Illuminate\Support\Facades\Facade;

class Panel extends Facade{
    protected static function getFacadeAccessor() { 
        return PanelManager::class; 
    }
}