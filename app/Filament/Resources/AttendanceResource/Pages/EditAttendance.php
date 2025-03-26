<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        // Cek apakah ada file lama yang tersimpan di public/keterangan
        if (!empty($this->record->file_keterangan)) {
            $oldFilePath = "keterangan/" . basename($this->record->file_keterangan);

            // Pastikan file lama ada sebelum menghapus
            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }

        return $data;
    }
}
