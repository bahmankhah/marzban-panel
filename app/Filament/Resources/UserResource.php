<?php

namespace App\Filament\Resources;

use App\Facades\Panel;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'danger',
                        'limited' => 'warning',
                        'expired' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('used_traffic')
                    ->label('Used Traffic')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A'),
                Tables\Columns\TextColumn::make('data_limit')
                    ->label('Data Limit')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024 / 1024 / 1024, 2) . ' GB' : 'Unlimited'),
                Tables\Columns\TextColumn::make('expire')
                    ->label('Expires')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i:s', $state) : 'Never'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i:s', strtotime($state)) : 'N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'limited' => 'Limited',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->visible(fn ($record) => $record->status !== 'disabled')
                    ->action(function ($record, $livewire) {
                        try {
                            $panel = Panel::marzban();
                            $panel->deactivateUser($record->username);
                            \Filament\Notifications\Notification::make()
                                ->title('User deactivated successfully.')
                                ->success()
                                ->send();
                            
                            // Refresh the table to reflect changes
                            $livewire->dispatch('refresh');
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error deactivating user: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->color('danger'),

                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->visible(fn ($record) => $record->status === 'disabled')
                    ->action(function ($record, $livewire) {
                        try {
                            $panel = Panel::marzban();
                            $panel->activateUser($record->username);
                            \Filament\Notifications\Notification::make()
                                ->title('User activated successfully.')
                                ->success()
                                ->send();
                            
                            // Refresh the table to reflect changes
                            $livewire->dispatch('refresh');
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error activating user: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->color('success'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([10, 25, 50, 100])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
