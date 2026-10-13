<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

/**
 * Form fields the content resources have in common.
 */
class ContentFields
{
    /**
     * A title field that fills in the slug as you type, on new records only.
     */
    public static function title(string $name = 'title'): TextInput
    {
        return TextInput::make($name)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (string $operation, ?string $state, callable $set): void {
                if ($operation === 'create') {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $prefix, string|bool $unique = true): TextInput
    {
        return TextInput::make('slug')
            ->label('Web address')
            ->prefix($prefix)
            ->required()
            ->alphaDash()
            ->maxLength(255)
            ->helperText('Changing this breaks links to the page. Add a redirect from the old address if you do.')
            ->unique(ignoreRecord: true, modifyRuleUsing: is_bool($unique) ? null : fn ($rule, $get) => $rule->where($unique, $get($unique)));
    }

    public static function photo(string $directory): Section
    {
        return Section::make('Photo')->columns(2)->collapsed(fn ($record): bool => blank($record?->hero_image))->schema([
            FileUpload::make('hero_image')->label('Photo')->image()->disk('public')->directory($directory)->imageEditor(),
            Textarea::make('hero_alt')
                ->label('Describe the photo')
                ->helperText('For people who cannot see it, and for search engines.')
                ->rows(3),
        ]);
    }

    /**
     * The "quick facts" block shown at the top of a page: short label and
     * value pairs that answer the obvious questions first.
     */
    public static function facts(): Repeater
    {
        return Repeater::make('facts')
            ->label('Quick facts')
            ->helperText('Shown at the top of the page. Keep each one short: "When", "Cost", "Who it suits".')
            ->schema([
                TextInput::make('label')->required()->maxLength(40),
                TextInput::make('value')->required()->maxLength(160),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->reorderable()
            ->addActionLabel('Add a fact');
    }

    public static function metaDescription(): Textarea
    {
        return Textarea::make('meta_description')
            ->label('Search description')
            ->helperText('What search engines and AI assistants show under the page title. Uses the summary if left empty. Aim for 120 to 160 characters.')
            ->rows(2)
            ->maxLength(255);
    }
}
