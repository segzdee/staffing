<?php

namespace App\Filament\Resources\ShiftApplicationResource\Pages;

use App\Filament\Resources\ShiftApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShiftApplications extends ListRecords
{
    protected static string $resource = ShiftApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
