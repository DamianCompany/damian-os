<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class FormularioProveedorServicioTecnico
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(self::pasos())
                ->columnSpanFull()
                ->extraAttributes(['class' => 'damian-supplier-wizard damian-technical-supplier-wizard']),
        ]);
    }

    public static function pasos(): array
    {
        return [
            Step::make('Proveedor')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Section::make('Datos principales')
                        ->description('Solo la información necesaria para identificarlo y contactarlo.')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                Select::make('tipo')
                                    ->label('Tipo de proveedor')
                                    ->options([
                                        'empresa' => 'Empresa',
                                        'persona' => 'Persona natural',
                                        'distribuidor' => 'Distribuidor',
                                        'fabricante' => 'Fabricante',
                                        'tienda' => 'Tienda',
                                    ])
                                    ->default('empresa')
                                    ->required()
                                    ->native(false),
                                TextInput::make('razon_social')
                                    ->label('Razón social / Nombre completo')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('nombre_comercial')
                                    ->label('Nombre comercial')
                                    ->placeholder('Opcional'),
                                Select::make('tipo_documento')
                                    ->label('Documento')
                                    ->options(['ruc' => 'RUC', 'dni' => 'DNI', 'otro' => 'Otro'])
                                    ->live()
                                    ->native(false),
                                TextInput::make('numero_documento')
                                    ->label('Número de documento')
                                    ->nullable()
                                    ->unique('proveedores_servicio_tecnico', 'numero_documento', ignoreRecord: true)
                                    ->rules(fn ($get): array => match ($get('tipo_documento')) {
                                        'ruc' => ['nullable', 'regex:/^\d{11}$/'],
                                        'dni' => ['nullable', 'regex:/^\d{8}$/'],
                                        default => ['nullable', 'max:30'],
                                    })
                                    ->validationMessages(['regex' => 'Revisa la cantidad de dígitos del documento.']),
                                TextInput::make('contacto_nombre')->label('Persona de contacto'),
                                TextInput::make('whatsapp')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->rules(['nullable', 'regex:/^[0-9+\s-]{9,20}$/']),
                                TextInput::make('telefono')->label('Teléfono')->tel(),
                                TextInput::make('correo')->label('Correo')->email(),
                            ]),
                        ]),
                ]),
            Step::make('Abastecimiento')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Section::make('Qué suministra')
                        ->description('Selecciona categorías y marcas; puedes crear una nueva sin salir del formulario.')
                        ->schema([
                            Select::make('categorias')
                                ->label('Categorías')
                                ->relationship('categorias', 'nombre', modifyQueryUsing: fn ($query) => $query->where('activo', true)->orderBy('orden'))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('nombre')->label('Nueva categoría')->required()->unique('categorias_proveedor_servicio_tecnico', 'nombre'),
                                ]),
                            Select::make('marcas')
                                ->label('Marcas')
                                ->relationship('marcas', 'nombre', modifyQueryUsing: fn ($query) => $query->where('activo', true)->orderBy('nombre'))
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->createOptionForm([
                                    TextInput::make('nombre')->label('Nueva marca')->required()->unique('marcas_proveedor_servicio_tecnico', 'nombre'),
                                ]),
                            Textarea::make('productos_principales')
                                ->label('Productos principales')
                                ->placeholder('Ejemplo: motoguadañas, repuestos Honda, lubricantes...')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
            Step::make('Condiciones')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Section::make('Condiciones básicas')
                        ->description('Información útil para decidir una compra o solicitar un repuesto.')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('entrega_promedio_dias')
                                    ->label('Entrega promedio')
                                    ->suffix('días')
                                    ->numeric()
                                    ->minValue(0),
                                Select::make('forma_pago')
                                    ->label('Forma de pago')
                                    ->options([
                                        'efectivo' => 'Efectivo',
                                        'transferencia' => 'Transferencia',
                                        'tarjeta' => 'Tarjeta',
                                        'contraentrega' => 'Contraentrega',
                                        'credito' => 'Crédito',
                                        'otro' => 'Otro',
                                    ])
                                    ->native(false),
                                TextInput::make('direccion')->label('Dirección')->columnSpanFull(),
                                Toggle::make('emite_factura')->label('Emite factura'),
                                Toggle::make('principal')->label('Proveedor principal'),
                                Select::make('estado')
                                    ->label('Estado')
                                    ->options(self::estados())
                                    ->default('evaluacion')
                                    ->required()
                                    ->native(false),
                                Textarea::make('notas')
                                    ->label('Notas internas')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        ]),
                ]),
        ];
    }

    public static function estados(): array
    {
        return [
            'evaluacion' => 'En evaluación',
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
        ];
    }
}
