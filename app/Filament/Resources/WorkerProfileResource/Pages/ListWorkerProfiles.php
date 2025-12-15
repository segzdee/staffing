<?php

namespace App\Filament\Resources\WorkerProfileResource\Pages;

use App\Filament\Resources\WorkerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkerProfiles extends ListRecords
{
    protected static string $resource = WorkerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
