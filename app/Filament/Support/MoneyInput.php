<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

class MoneyInput
{
    /**
     * A pounds-and-pence field for a column that stores integer pence.
     */
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('£')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->formatStateUsing(fn ($state): ?string => blank($state) ? null : number_format(((int) $state) / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state): ?int => blank($state) ? null : (int) round(((float) $state) * 100));
    }
}
