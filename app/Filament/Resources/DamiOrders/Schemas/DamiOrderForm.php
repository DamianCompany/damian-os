<?php

namespace App\Filament\Resources\DamiOrders\Schemas;

use App\Models\Printer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class DamiOrderForm
{
    private static function recalculate(Get $get, Set $set): void
    {
        $filament = round(((float) $get('filament_grams') / 1000) * 100, 2);
        $cost = round($filament + (float) $get('electricity_cost') + (float) $get('labor_cost'), 2);
        $price = round((int) $get('quantity') * (float) $get('unit_sale_price'), 2);

        $set('filament_cost', $filament);
        $set('total_cost', $cost);
        $set('total_price', $price);
        $set('profit', round($price - $cost, 2));
        $set('pending_balance', round($price - (float) $get('advance'), 2));
    }

    /**
     * @return array<Step>
     */
    public static function steps(): array
    {
        return [
            Step::make('Cliente')
                ->icon(Heroicon::OutlinedUser)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->schema([
                    Section::make('Datos del pedido')
                        ->description('Identifica al cliente y lo que necesita.')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('order_number')
                                    ->label('Orden')
                                    ->placeholder('Se genera automáticamente')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'in_progress' => 'En proceso',
                                        'completed' => 'Completado',
                                    ])
                                    ->default('pending')
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->required(),
                            ]),
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('client_name')
                                    ->label('Nombres / Razón social')
                                    ->required()
                                    ->minLength(3)
                                    ->maxLength(255),
                                TextInput::make('client_document')
                                    ->label('DNI / RUC')
                                    ->placeholder('Opcional')
                                    ->nullable()
                                    ->numeric()
                                    ->rule('regex:/^(?:\d{8}|\d{11})$/')
                                    ->validationMessages([
                                        'regex' => 'Ingresa un DNI de 8 dígitos o un RUC de 11 dígitos.',
                                    ]),
                            ]),
                            Textarea::make('description')
                                ->label('Descripción')
                                ->placeholder('Ejemplo: figura de león de 20 cm, acabado mate...')
                                ->required()
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                ]),

            Step::make('Archivos')
                ->icon(Heroicon::OutlinedPhoto)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->schema([
                    Section::make('Archivos del cliente')
                        ->description('Añade únicamente lo necesario para producir.')
                        ->icon(Heroicon::OutlinedDocumentArrowUp)
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 2])->schema([
                                FileUpload::make('reference_images')
                                    ->label('Imágenes referenciales')
                                    ->helperText('Hasta 3 imágenes.')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(3)
                                    ->directory('dami-3d/references')
                                    ->reorderable()
                                    ->imagePreviewHeight('180'),
                                FileUpload::make('received_file')
                                    ->label('Archivo para producción')
                                    ->helperText('STL, 3MF, OBJ, STEP, STP, GCODE, ZIP o PDF. Máximo 250 MB.')
                                    ->directory('dami-3d/files')
                                    ->maxSize(256000)
                                    ->acceptedFileTypes([
                                        'model/stl',
                                        'application/sla',
                                        'application/vnd.ms-pki.stl',
                                        'model/3mf',
                                        'application/vnd.ms-package.3dmanufacturing-3dmodel+xml',
                                        'model/obj',
                                        'model/step',
                                        'application/step',
                                        'text/plain',
                                        'application/octet-stream',
                                        'application/zip',
                                        'application/x-zip-compressed',
                                        'application/pdf',
                                    ])
                                    ->rules(['extensions:stl,3mf,obj,step,stp,gcode,zip,pdf']),
                            ]),
                        ]),
                ]),

            Step::make('Producción')
                ->icon(Heroicon::OutlinedCube)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->schema([
                    Section::make('Plan de producción')
                        ->description('Define fechas, material y tiempos.')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                DatePicker::make('start_date')
                                    ->label('Fecha de inicio')
                                    ->required()
                                    ->native(false),
                                DatePicker::make('end_date')
                                    ->label('Fecha de término')
                                    ->required()
                                    ->native(false)
                                    ->minDate(fn (Get $get) => $get('start_date'))
                                    ->afterOrEqual('start_date')
                                    ->validationMessages([
                                        'after_or_equal' => 'La fecha de término no puede ser anterior al inicio.',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set) => $set(
                                        'delivery_date',
                                        $state ? Carbon::parse($state)->addDays(3)->toDateString() : null,
                                    )),
                                DatePicker::make('delivery_date')
                                    ->label('Entrega estimada')
                                    ->helperText('Término + 3 días')
                                    ->disabled()
                                    ->dehydrated()
                                    ->native(false),
                            ]),
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('filament_grams')
                                    ->label('Filamento')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100000)
                                    ->suffix('g')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                                Select::make('filament_type')
                                    ->label('Tipo de filamento')
                                    ->options(['PLA' => 'PLA', 'PETG' => 'PETG', 'ABS' => 'ABS'])
                                    ->native(false)
                                    ->required(),
                                TextInput::make('print_hours')
                                    ->label('Tiempo de impresión')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10000)
                                    ->suffix('h')
                                    ->default(0)
                                    ->required(),
                                TextInput::make('postprocess_hours')
                                    ->label('Tiempo de postproceso')
                                    ->helperText('Opcional; se puede completar durante el seguimiento.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10000)
                                    ->suffix('h'),
                            ]),
                        ]),
                ]),

            Step::make('Costos')
                ->icon(Heroicon::OutlinedBanknotes)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->schema([
                    Section::make('Costos y venta')
                        ->description('Los resultados se actualizan mientras escribes.')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('quantity')
                                    ->label('Cantidad a producir')
                                    ->helperText('Número total de unidades que se imprimirán.')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(10000)
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                                TextInput::make('unit_sale_price')
                                    ->label('Precio por unidad')
                                    ->helperText('Venta total = cantidad × precio por unidad.')
                                    ->prefix('S/')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->maxValue(1000000)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                                TextInput::make('electricity_cost')
                                    ->label('Electricidad')
                                    ->prefix('S/')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1000000)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                                TextInput::make('labor_cost')
                                    ->label('Mano de obra')
                                    ->prefix('S/')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1000000)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                                TextInput::make('advance')
                                    ->label('Adelanto')
                                    ->prefix('S/')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100000000)
                                    ->lte('total_price')
                                    ->validationMessages([
                                        'lte' => 'El adelanto no puede superar el total del pedido.',
                                    ])
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                            ]),
                            Grid::make(['default' => 2, 'lg' => 4])
                                ->extraAttributes(['class' => 'damian-cost-summary'])
                                ->schema([
                                    TextInput::make('filament_cost')
                                        ->label('Filamento')
                                        ->prefix('S/')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                    TextInput::make('total_cost')
                                        ->label('Costo total')
                                        ->prefix('S/')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                    TextInput::make('total_price')
                                        ->label('Venta total')
                                        ->prefix('S/')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                    TextInput::make('profit')
                                        ->label('Ganancia')
                                        ->prefix('S/')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0),
                                    TextInput::make('pending_balance')
                                        ->label('Saldo pendiente')
                                        ->prefix('S/')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(0)
                                        ->visible(fn (Get $get): bool => (float) $get('advance') > 0),
                                ]),
                        ]),
                ]),

            Step::make('Asignación')
                ->icon(Heroicon::OutlinedPrinter)
                ->completedIcon(Heroicon::OutlinedCheckCircle)
                ->schema([
                    Section::make('Asignar producción')
                        ->description('El responsable se asigna al pedido, no a la impresora.')
                        ->icon(Heroicon::OutlinedPrinter)
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('responsible_name')
                                    ->label('Responsable del pedido')
                                    ->minLength(3)
                                    ->maxLength(255)
                                    ->required(),
                                Select::make('printer_id')
                                    ->label('Impresora')
                                    ->options(fn () => Printer::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Set $set) => $set(
                                        'printer_location',
                                        Printer::find($state)?->location,
                                    )),
                                TextInput::make('printer_location')
                                    ->label('Ubicación')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ]),
                        ]),
                ]),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(self::steps())
                ->contained(false)
                ->extraAttributes(['class' => 'damian-order-wizard']),
        ]);
    }
}
