<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PricingRuleResource\Pages;
use App\Filament\Admin\Resources\PricingRuleResource\RelationManagers;
use App\Models\PricingRule;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A descriptive name for this pricing rule.'),

                TextInput::make('price_per_hour')
                    ->label('Price per Hour')
                    ->prefix('Rp')
                    ->numeric()
                    ->required(),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        'regular' => 'Regular',
                        'peak' => 'Peak',
                        'promo' => 'Promo',
                        'custom' => 'Custom',
                    ])
                    ->default('regular'),

                TextInput::make('priority')
                    ->label('Priority')
                    ->numeric()
                    ->default(0)
                    ->helperText('Higher priority rules override lower ones.'),

                Select::make('court_id')
                    ->label('Court')
                    ->relationship('court', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->helperText('Leave blank to apply to all courts.'),

                Select::make('day_of_week')
                    ->label('Day of Week')
                    ->options([
                        'null' => 'All Days',
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->nullable()
                    ->helperText('Leave blank to apply to all days.'),

                Select::make('time_start')
                    ->label('Start Time')
                    ->options(
                        collect(range(0, 23))->mapWithKeys(fn($h) => [
                            sprintf('%02d:00:00', $h) => sprintf('%02d:00', $h),
                        ])->toArray()
                    )
                    ->required()
                    ->reactive()
                    ->extraAttributes(['size' => 5])
                    ->native(false),

                Select::make('time_end')
                    ->label('End Time')
                    ->options(function (Get $get) {
                        $start = $get('time_start');

                        if (!$start) {
                            return [];
                        }

                        $startHour = (int) explode(':', $start)[0];

                        return collect(range(0, 23))
                            ->filter(
                                fn($h) => ($startHour === 0 && $h === 0) ||
                                    ($h > $startHour) ||
                                    ($startHour === 23 && $h === 0)
                            )
                            ->mapWithKeys(fn($h) => [
                                sprintf('%02d:00:00', $h) => sprintf('%02d:00', $h),
                            ])
                            ->toArray();
                    })
                    ->required()
                    ->live()
                    ->extraAttributes(['size' => 5])
                    ->afterStateHydrated(function (Select $component, $state) {
                        $options = $component->getOptions();
                        if ($state && ! array_key_exists($state, $options)) {
                            $component->state(null);
                        }
                    })
                    ->native(false),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->nullable()
                    ->helperText('Leave blank to apply from any date.'),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->nullable()
                    ->helperText('Leave blank to apply indefinitely.'),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->maxLength(1000)
                    ->helperText('Optional description for this pricing rule.')
                    ->columnSpanFull(),

                Placeholder::make('created_by')
                    ->label('Created By')
                    ->content(fn(PricingRule $record): string => $record->creator->name ?? '-')
                    ->visible(fn(Get $get) => filled($get('id'))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('court.name')
                    ->label('Court')
                    ->sortable()
                    ->searchable()
                    ->default('All Courts'),

                TextColumn::make('day_of_week')
                    ->label('Day of Week')
                    ->sortable()
                    ->searchable()
                    ->default('All Days')
                    ->formatStateUsing(fn($state) => match ($state) {
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        default => 'All Days',
                    }),

                TextColumn::make('time_start')
                    ->label('Start Time')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => Carbon::createFromTimeString($state)->format('H:i')),


                TextColumn::make('time_end')
                    ->label('End Time')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => Carbon::createFromTimeString($state)->format('H:i')),

                TextColumn::make('price_per_hour')
                    ->label('Price per Hour')
                    ->sortable()
                    ->searchable()
                    ->prefix('Rp')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->sortable()
                    ->searchable(),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}
