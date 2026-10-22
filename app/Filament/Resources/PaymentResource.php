<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Activations';

    protected static ?string $modelLabel = 'activation payment';

    // Admins only ever verify or inspect payments here — never hand-edit or
    // delete them, since that would bypass the wallet-crediting logic.
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.referral_code')
                    ->label('Referral Code'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN', divideBy: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Verified',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Verify payment received')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This credits the ₦10,000 welcome bonus to the user\'s wallet and marks their account verified. This cannot be undone from here.')
                    ->visible(fn (Payment $record) => $record->status === 'pending')
                    ->action(function (Payment $record) {
                        $record->verifyAndCreditUser();

                        Notification::make()
                            ->title('Payment verified')
                            ->body($record->user->full_name.'\'s account is now verified.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }
}
