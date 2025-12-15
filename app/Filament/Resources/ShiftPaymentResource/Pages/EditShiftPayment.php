<?php

namespace App\Filament\Resources\ShiftPaymentResource\Pages;

use App\Filament\Resources\ShiftPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShiftPayment extends EditRecord
{
    protected static string $resource = ShiftPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
