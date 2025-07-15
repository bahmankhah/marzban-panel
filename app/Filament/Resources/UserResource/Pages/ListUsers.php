<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Facades\Panel;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableRecords(): Collection
    {
        $panel = Panel::marzban();

        // Get Filament table query parameters
        $search = $this->getTableSearch();
        $limit = $this->getTableRecordsPerPage();
        $page = $this->getTablePage();
        $offset = ($page - 1) * $limit;

        $sortColumn = $this->getTableSortColumn();
        $sortDirection = $this->getTableSortDirection();

        // Map Filament sort direction to API
        $sort = $sortColumn ? [
            'field' => $sortColumn,
            'direction' => $sortDirection,
        ] : null;

        // Prepare API parameters
        $params = [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset,
        ];
        if ($sort) {
            $str = ($sort['direction'] == 'desc' ? '-' : '' ) . $sort['field'];
            $params['sort'] = $str;
        }

        $response = $panel->getUsers(...$params);
        $users = collect($response['users'] ?? []);

        $mappedUsers = $users->map(function ($userData) {

            $user = new \App\Models\User();
            $userData['id'] = $userData['username']; 
            $user->forceFill($userData);
            $user->exists = true;
            return $user;
        });

        return new Collection($mappedUsers->all());
    }
    
    public function getTableRecordKey($record): string
    {
        return $record->username;
    }
}
