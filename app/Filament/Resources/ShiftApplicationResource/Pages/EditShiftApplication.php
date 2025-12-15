<?php

namespace App\Filament\Resources\ShiftApplicationResource\Pages;

use App\Filament\Resources\ShiftApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiftApplication extends EditRecord
{
    protected static string $resource = ShiftApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
