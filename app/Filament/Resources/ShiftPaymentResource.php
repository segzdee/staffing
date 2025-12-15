<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftPaymentResource\Pages;
use App\Filament\Resources\ShiftPaymentResource\RelationManagers;
use App\Models\ShiftPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftPaymentResource extends Resource
{
    protected static ?string $model = ShiftPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shift_assignment_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('worker_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('business_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('amount_gross')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('platform_fee')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('vat_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('worker_tax_withheld')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('tax_year')
                    ->maxLength(191),
                Forms\Components\TextInput::make('tax_quarter')
                    ->maxLength(191),
                Forms\Components\Toggle::make('reported_to_tax_authority')
                    ->required(),
                Forms\Components\TextInput::make('platform_revenue')
                    ->numeric(),
                Forms\Components\TextInput::make('payment_processor_fee')
                    ->numeric(),
                Forms\Components\TextInput::make('net_platform_revenue')
                    ->numeric(),
                Forms\Components\TextInput::make('agency_commission')
                    ->numeric(),
                Forms\Components\TextInput::make('worker_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('amount_net')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('escrow_held_at'),
                Forms\Components\DateTimePicker::make('released_at'),
                Forms\Components\DateTimePicker::make('payout_initiated_at'),
                Forms\Components\DateTimePicker::make('payout_completed_at'),
                Forms\Components\TextInput::make('payout_delay_minutes')
                    ->numeric(),
                Forms\Components\TextInput::make('payout_speed')
                    ->required(),
                Forms\Components\Toggle::make('early_payout_requested')
                    ->required(),
                Forms\Components\TextInput::make('early_payout_fee')
                    ->numeric(),
                Forms\Components\Toggle::make('requires_manual_review')
                    ->required(),
                Forms\Components\Textarea::make('manual_review_reason')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('reviewed_at'),
                Forms\Components\TextInput::make('reviewed_by_admin_id')
                    ->numeric(),
                Forms\Components\Textarea::make('internal_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('stripe_payment_intent_id')
                    ->maxLength(191),
                Forms\Components\TextInput::make('stripe_transfer_id')
                    ->maxLength(191),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('disputed')
                    ->required(),
                Forms\Components\Textarea::make('dispute_reason')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('disputed_at'),
                Forms\Components\TextInput::make('dispute_filed_by')
                    ->maxLength(191),
                Forms\Components\TextInput::make('dispute_status'),
                Forms\Components\Textarea::make('dispute_evidence_url')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('dispute_resolution_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('dispute_adjustment_amount')
                    ->numeric(),
                Forms\Components\Toggle::make('is_refunded')
                    ->required(),
                Forms\Components\TextInput::make('refund_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('refund_reason')
                    ->maxLength(191),
                Forms\Components\DateTimePicker::make('refunded_at'),
                Forms\Components\TextInput::make('stripe_refund_id')
                    ->maxLength(191),
                Forms\Components\TextInput::make('adjustment_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Textarea::make('adjustment_notes')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('resolved_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shift_assignment_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_gross')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_tax_withheld')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_year')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_quarter')
                    ->searchable(),
                Tables\Columns\IconColumn::make('reported_to_tax_authority')
                    ->boolean(),
                Tables\Columns\TextColumn::make('platform_revenue')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_processor_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_platform_revenue')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency_commission')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_net')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('escrow_held_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('released_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_initiated_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_delay_minutes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_speed'),
                Tables\Columns\IconColumn::make('early_payout_requested')
                    ->boolean(),
                Tables\Columns\TextColumn::make('early_payout_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_manual_review')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_by_admin_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_payment_intent_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stripe_transfer_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('disputed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('disputed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dispute_filed_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dispute_status'),
                Tables\Columns\TextColumn::make('dispute_adjustment_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_refunded')
                    ->boolean(),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('refund_reason')
                    ->searchable(),
                Tables\Columns\TextColumn::make('refunded_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stripe_refund_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('adjustment_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime()
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
            'index' => Pages\ListShiftPayments::route('/'),
            'create' => Pages\CreateShiftPayment::route('/create'),
            'edit' => Pages\EditShiftPayment::route('/{record}/edit'),
        ];
    }
}
