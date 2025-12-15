<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(191),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\DateTimePicker::make('dev_expires_at'),
                Forms\Components\Toggle::make('is_dev_account')
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('username')
                    ->maxLength(191),
                Forms\Components\TextInput::make('role')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('mfa_enabled')
                    ->required(),
                Forms\Components\TextInput::make('user_type')
                    ->required(),
                Forms\Components\Toggle::make('is_verified_worker')
                    ->required(),
                Forms\Components\Toggle::make('is_verified_business')
                    ->required(),
                Forms\Components\TextInput::make('onboarding_step')
                    ->maxLength(191),
                Forms\Components\Toggle::make('onboarding_completed')
                    ->required(),
                Forms\Components\TextInput::make('notification_preferences'),
                Forms\Components\TextInput::make('availability_schedule'),
                Forms\Components\TextInput::make('max_commute_distance')
                    ->numeric(),
                Forms\Components\TextInput::make('rating_as_worker')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('rating_as_business')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_shifts_completed')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_shifts_posted')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('reliability_score')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Textarea::make('bio')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dev_expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_dev_account')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('mfa_enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user_type'),
                Tables\Columns\IconColumn::make('is_verified_worker')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified_business')
                    ->boolean(),
                Tables\Columns\TextColumn::make('onboarding_step')
                    ->searchable(),
                Tables\Columns\IconColumn::make('onboarding_completed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('max_commute_distance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_as_worker')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_as_business')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_shifts_completed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_shifts_posted')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reliability_score')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
