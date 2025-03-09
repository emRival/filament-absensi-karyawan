<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        OSMMap::make('location')
                            ->label('Location')
                            ->showMarker()
                            ->draggable()
                            ->extraControl([
                                'zoomDelta'           => 1,
                                'zoomSnap'            => 0.25,
                                'wheelPxPerZoomLevel' => 60
                            ])
                            ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, $record) {
                                $latitude = $record->latitude ?? 0;
                                $longitude = $record->longitude ?? 0;


                                if ($latitude && $longitude) {
                                    $set('location', [
                                        'lat' => $latitude,
                                        'lng' => $longitude,
                                        'minZoom' => 1,
                                        'maxZoom' => 23,
                                        'zoom' => 19,
                                        'ext' => 'png'
                                    ]);
                                }
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                                $set('latitude', $state['lat']);
                                $set('longitude', $state['lng']);
                            })
                            ->tilesUrl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),

                        Group::make([
                            Forms\Components\TextInput::make('latitude')
                                ->required()
                                ->default(0)
                                ->numeric(),
                            Forms\Components\TextInput::make('longitude')
                                ->required()
                                ->default(0)
                                ->numeric(),
                        ])->columns(2),

                    ])
                ]),
                Group::make([
                    Section::make([


                        Forms\Components\TextInput::make('radius')
                            ->required()
                            ->numeric(),
                    ])
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')

                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')

                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('radius')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
