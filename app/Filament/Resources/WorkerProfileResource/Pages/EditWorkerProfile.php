<?php

namespace App\Filament\Resources\WorkerProfileResource\Pages;

use App\Filament\Resources\WorkerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkerProfile extends EditRecord
{
    protected static string $resource = WorkerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
