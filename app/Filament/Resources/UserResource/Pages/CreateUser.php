<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Facades\Panel;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function create(bool $another = false): void{
        $panel = Panel::authorize(Filament::auth()->user()->panel_username, Filament::auth()->user()->panel_password);
        $panel->createUser($this->data['username']);
    }

}
