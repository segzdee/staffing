<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftApplicationResource\Pages;
use App\Filament\Resources\ShiftApplicationResource\RelationManagers;
use App\Models\ShiftApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftApplicationResource extends Resource
{
    protected static ?string $model = ShiftApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shift_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('worker_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('match_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('skill_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('proximity_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('reliability_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('rating_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('recency_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('rank_position')
                    ->numeric(),
                Forms\Components\TextInput::make('distance_km')
                    ->numeric(),
                Forms\Components\TextInput::make('priority_tier')
                    ->required(),
                Forms\Components\Textarea::make('application_note')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('applied_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('notification_sent_at'),
                Forms\Components\DateTimePicker::make('notification_opened_at'),
                Forms\Components\DateTimePicker::make('responded_at'),
                Forms\Components\DateTimePicker::make('acknowledged_at'),
                Forms\Components\DateTimePicker::make('acknowledgment_required_by'),
                Forms\Components\DateTimePicker::make('reminder_sent_at'),
                Forms\Components\DateTimePicker::make('auto_cancelled_at'),
                Forms\Components\Toggle::make('acknowledgment_late')
                    ->required(),
                Forms\Components\Toggle::make('is_favorited')
                    ->required(),
                Forms\Components\Toggle::make('is_blocked')
                    ->required(),
                Forms\Components\TextInput::make('application_source')
                    ->required()
                    ->maxLength(191)
                    ->default('mobile_app'),
                Forms\Components\TextInput::make('device_type')
                    ->maxLength(191),
                Forms\Components\TextInput::make('app_version')
                    ->maxLength(191),
                Forms\Components\DateTimePicker::make('viewed_by_business_at'),
                Forms\Components\TextInput::make('responded_by')
                    ->numeric(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shift_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('match_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('skill_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('proximity_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reliability_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recency_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rank_position')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance_km')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority_tier'),
                Tables\Columns\TextColumn::make('applied_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notification_sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notification_opened_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responded_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('acknowledged_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('acknowledgment_required_by')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reminder_sent_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('auto_cancelled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('acknowledgment_late')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_favorited')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_blocked')
                    ->boolean(),
                Tables\Columns\TextColumn::make('application_source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('viewed_by_business_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responded_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListShiftApplications::route('/'),
            'create' => Pages\CreateShiftApplication::route('/create'),
            'edit' => Pages\EditShiftApplication::route('/{record}/edit'),
        ];
    }
}
