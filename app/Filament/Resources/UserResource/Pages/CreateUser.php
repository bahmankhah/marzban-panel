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
        try {
            $panel = Panel::marzban();
            $panel->createUser($this->data['username']);
            $this->notify('success', 'User created successfully.');
            $this->halt();
            $this->redirect($this->getRedirectUrl());
        } catch (\Throwable $e) {
            $this->notify('danger', 'Error creating user: ' . $e->getMessage());
        }
    }

}
