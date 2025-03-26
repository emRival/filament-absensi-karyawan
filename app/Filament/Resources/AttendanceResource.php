<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\File\File as SplFileInfo;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(fn() => !Auth::user()->roles->pluck('id')->contains(1)),

                Forms\Components\Select::make('status')
                    ->options(fn() => Auth::user()->roles->pluck('id')->contains(1) ? [
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ] : [
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('start_time')
                    ->label('Jam Masuk')
                    ->disabled(),

                Forms\Components\TextInput::make('end_time')
                    ->label('Jam Pulang')
                    ->disabled(),

                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull()
                    ->required(fn(callable $get) => in_array($get('status'), ['Sakit', 'Izin'])),

                Forms\Components\FileUpload::make('file_keterangan')
                    ->label('File Keterangan')
                    ->disk('public')
                    ->directory('keterangan')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(1024 * 10)
                    ->openable()
                    ->downloadable()
                    ->required(fn(callable $get) => $get('status') === 'Sakit')
                    ->columnSpanFull()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get) {
                        $date = now()->format('Y-m-d');
                        $name = Auth::user()->name;
                        $status = $get('status');
                        return "{$date}-{$name}-{$status}.{$file->getClientOriginalExtension()}";
                    }),

                Forms\Components\TextInput::make('keterlambatan')
                    ->suffix(' Menit')
                    ->numeric()
                    ->maxLength(3)
                    ->visible(fn() => Auth::user()->roles->pluck('id')->contains(1)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->roles->pluck('id')->contains(1);

                if (!$is_super_admin) {
                    $query->where('user_id', Auth::id());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Karyawan'),


                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Masuk')
                    ->badge()
                    ->description(fn(Attendance $record) => Carbon::parse($record->start_time)->lt(Carbon::parse($record->schedule_start_time)) ? 'Datang Lebih Awal ' . "(" . (int) abs(Carbon::parse($record->schedule_start_time)->diffInMinutes(Carbon::parse($record->start_time), false))  . ' Menit)' : ($record->start_time == $record->schedule_start_time ? 'Tepat Waktu' : 'Terlambat '))
                    ->color(fn(Attendance $record) => $record->start_time < $record->schedule_start_time ? 'success' : ($record->start_time == $record->schedule_start_time ? 'primary' : 'danger')),


                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Pulang')
                    ->badge()
                    ->description(
                        fn(Attendance $record) => (
                            Carbon::parse($record->end_time)->lt(Carbon::parse($record->schedule_end_time))
                            ? 'Pulang Lebih Awal ' . "(" . (int) abs(Carbon::parse($record->schedule_end_time)->diffInMinutes(Carbon::parse($record->end_time), false)) . ' Menit)'
                            : (Carbon::parse($record->end_time)->eq(Carbon::parse($record->schedule_end_time))
                                ? 'Tepat Waktu'
                                : 'Overtime ' . "(" . (int) abs(Carbon::parse($record->end_time)->diffInMinutes(Carbon::parse($record->schedule_end_time), false)) . ' Menit)'
                            )
                        )

                    )
                    ->color(
                        fn(Attendance $record) => (
                            Carbon::parse($record->end_time)->lt(Carbon::parse($record->schedule_end_time))
                            ? 'danger'
                            : (Carbon::parse($record->end_time)->eq(Carbon::parse($record->schedule_end_time)) ? 'primary' : 'success')
                        )

                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->color(fn($state) => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'warning',
                        'Sakit' => 'danger',
                    })
                    ->icon(fn($state) => match ($state) {
                        'Hadir' => 'heroicon-o-check-circle',
                        'Izin' => 'heroicon-o-exclamation-circle',
                        'Sakit' => 'heroicon-o-x-circle',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->description(fn(Attendance $record) => $record->file_keterangan ? "Ada File Keterangan" : ''),
                Tables\Columns\TextColumn::make('keterlambatan')
                    ->suffix(' Menit')
                    ->icon('heroicon-o-clock')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'default')
                    ->toggleable(),


            ])
            ->filters([
                //
            ])
            ->actions([

                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    //download file_keterangan
                    Tables\Actions\Action::make('download')
                        ->label('Download File Keterangan')
                        ->url(fn(Attendance $record) => $record->file_keterangan ? Storage::url($record->file_keterangan) : null)
                        ->icon('heroicon-o-arrow-down-tray')
                        ->openUrlInNewTab()
                        ->requiresConfirmation()
                        ->visible(fn(Attendance $record) => $record->file_keterangan !== null),
                ])->tooltip('Actions'),

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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
