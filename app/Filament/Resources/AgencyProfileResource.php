<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgencyProfileResource\Pages;
use App\Filament\Resources\AgencyProfileResource\RelationManagers;
use App\Models\AgencyProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgencyProfileResource extends Resource
{
    protected static ?string $model = AgencyProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('onboarding_completed')
                    ->required(),
                Forms\Components\TextInput::make('onboarding_step')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('onboarding_completed_at'),
                Forms\Components\TextInput::make('agency_name')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('license_number')
                    ->maxLength(191),
                Forms\Components\Toggle::make('license_verified')
                    ->required(),
                Forms\Components\TextInput::make('verification_status')
                    ->required()
                    ->maxLength(191)
                    ->default('pending'),
                Forms\Components\Textarea::make('verification_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('business_model')
                    ->required(),
                Forms\Components\TextInput::make('commission_rate')
                    ->required()
                    ->numeric()
                    ->default(10.00),
                Forms\Components\TextInput::make('variable_commission_rate')
                    ->numeric(),
                Forms\Components\TextInput::make('total_commission_earned')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('pending_commission')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('paid_commission')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Toggle::make('urgent_fill_enabled')
                    ->required(),
                Forms\Components\TextInput::make('urgent_fill_commission_multiplier')
                    ->required()
                    ->numeric()
                    ->default(1.50),
                Forms\Components\TextInput::make('urgent_fills_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('average_urgent_fill_time_hours')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('fill_rate')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('shifts_declined')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('worker_dropouts')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('client_satisfaction_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('repeat_clients')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('managed_workers'),
                Forms\Components\TextInput::make('total_shifts_managed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_workers_managed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('active_workers')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('available_workers')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('average_worker_rating')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('worker_skill_distribution'),
                Forms\Components\TextInput::make('business_registration_number')
                    ->maxLength(191),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(191),
                Forms\Components\TextInput::make('website')
                    ->maxLength(191),
                Forms\Components\TextInput::make('address')
                    ->maxLength(191),
                Forms\Components\TextInput::make('city')
                    ->maxLength(100),
                Forms\Components\TextInput::make('state')
                    ->maxLength(100),
                Forms\Components\TextInput::make('zip_code')
                    ->maxLength(20),
                Forms\Components\TextInput::make('country')
                    ->maxLength(100),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('specializations'),
                Forms\Components\TextInput::make('total_workers')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_placements')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Toggle::make('is_verified')
                    ->required(),
                Forms\Components\DateTimePicker::make('verified_at'),
                Forms\Components\Toggle::make('is_complete')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('onboarding_completed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('onboarding_step')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('onboarding_completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('license_number')
                    ->searchable(),
                Tables\Columns\IconColumn::make('license_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('verification_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_model'),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variable_commission_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_commission_earned')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_commission')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_commission')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('urgent_fill_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('urgent_fill_commission_multiplier')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('urgent_fills_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_urgent_fill_time_hours')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fill_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shifts_declined')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_dropouts')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_satisfaction_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('repeat_clients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_shifts_managed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_workers_managed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_workers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_workers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_worker_rating')
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
                Tables\Columns\TextColumn::make('business_registration_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_workers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_placements')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_complete')
                    ->boolean(),
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
            'index' => Pages\ListAgencyProfiles::route('/'),
            'create' => Pages\CreateAgencyProfile::route('/create'),
            'edit' => Pages\EditAgencyProfile::route('/{record}/edit'),
        ];
    }
}
