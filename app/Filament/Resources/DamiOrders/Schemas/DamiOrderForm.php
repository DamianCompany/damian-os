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
use Filament\Schemas\Schema;
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

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información general')
                    ->description('Datos principales del pedido y del cliente.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('order_number')->label('Orden')->placeholder('Se genera automáticamente')->disabled()->dehydrated(false),
                            TextInput::make('quantity')->label('Cantidad')->numeric()->minValue(1)->default(1)->required()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                            Select::make('status')->label('Estado')->options([
                                'new' => 'Nuevo',
                                'planned' => 'Planificado',
                                'in_progress' => 'En proceso',
                                'review' => 'Revisión',
                                'ready' => 'Terminado',
                                'blocked' => 'Bloqueado',
                                'delivered' => 'Entregado',
                            ])->default('new')->required(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('client_name')->label('Nombres / Razón social')->required()->maxLength(255),
                            TextInput::make('client_document')->label('DNI / RUC')->required()->maxLength(20),
                        ]),
                        Textarea::make('description')->label('Descripción')->required()->rows(3)->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Archivos e imágenes')
                    ->description('Hasta 3 imágenes referenciales y un archivo de producción.')
                    ->schema([
                        FileUpload::make('reference_images')->label('Imágenes referenciales')->image()->multiple()->maxFiles(3)->directory('dami-3d/references')->reorderable(),
                        FileUpload::make('received_file')->label('Archivo recibido')->directory('dami-3d/files')->acceptedFileTypes([
                            'model/stl', 'application/octet-stream', 'application/zip', 'application/pdf',
                        ]),
                    ])->columns(2)->columnSpanFull(),

                Section::make('Planificación')
                    ->schema([
                        DatePicker::make('start_date')->label('Fecha de inicio')->required()->native(false),
                        DatePicker::make('end_date')->label('Fecha de término')->required()->native(false)->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('delivery_date', $state ? Carbon::parse($state)->addDays(3)->toDateString() : null)),
                        DatePicker::make('delivery_date')->label('Fecha de entrega (+3 días)')->disabled()->dehydrated()->native(false),
                    ])->columns(3)->columnSpanFull(),

                Section::make('Materiales y tiempos')
                    ->schema([
                        TextInput::make('filament_grams')->label('Cantidad de filamento')->numeric()->minValue(0)->suffix('g')->required()->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                        Select::make('filament_type')->label('Tipo de filamento')->options(['PLA' => 'PLA', 'PETG' => 'PETG', 'ABS' => 'ABS'])->required(),
                        TextInput::make('print_hours')->label('Tiempo de impresión')->numeric()->minValue(0)->suffix('h')->default(0)->required(),
                        TextInput::make('postprocess_hours')->label('Tiempo de postproceso')->numeric()->minValue(0)->suffix('h')->default(0)->required(),
                    ])->columns(4)->columnSpanFull(),

                Section::make('Costos y precios')
                    ->description('Los totales se calculan automáticamente en soles.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('filament_cost')->label('Costo de filamento')->prefix('S/')->disabled()->dehydrated()->default(0),
                            TextInput::make('electricity_cost')->label('Costo adicional (electricidad)')->prefix('S/')->numeric()->minValue(0)->default(0)->required()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                            TextInput::make('labor_cost')->label('Costo de mano de obra')->prefix('S/')->numeric()->minValue(0)->default(0)->required()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                            TextInput::make('unit_sale_price')->label('Precio unitario de venta')->prefix('S/')->numeric()->minValue(0)->required()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                            TextInput::make('advance')->label('Adelanto')->helperText('Si no existe adelanto, ingresa 0.')->prefix('S/')->numeric()->minValue(0)->default(0)->required()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('total_cost')->label('Costo total')->prefix('S/')->disabled()->dehydrated()->default(0),
                            TextInput::make('total_price')->label('Total del pedido')->prefix('S/')->disabled()->dehydrated()->default(0),
                            TextInput::make('profit')->label('Ganancia')->prefix('S/')->disabled()->dehydrated()->default(0),
                            TextInput::make('pending_balance')->label('Saldo pendiente')->prefix('S/')->disabled()->dehydrated()->default(0)
                                ->visible(fn (Get $get): bool => (float) $get('advance') > 0),
                        ]),
                    ])->columnSpanFull(),

                Section::make('Responsable e impresora')
                    ->schema([
                        TextInput::make('responsible_name')->label('Responsable')->required(),
                        Select::make('printer_id')->label('Impresora')->options(fn () => Printer::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()->required()->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('printer_location', Printer::find($state)?->location)),
                        TextInput::make('printer_location')->label('Ubicación')->disabled()->dehydrated()->required(),
                        TextInput::make('authorized_responsible')->label('Responsable autorizado')->required(),
                    ])->columns(2)->columnSpanFull(),
            ]);
    }
}
