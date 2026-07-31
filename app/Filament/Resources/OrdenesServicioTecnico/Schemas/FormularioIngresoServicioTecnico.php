<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Schemas;

use App\Models\ProveedorServicioTecnico;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FormularioIngresoServicioTecnico
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cliente y equipo')
                ->description('Solo registra lo necesario para recibir el equipo.')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('cliente')->label('Cliente')->required()->minLength(3)->maxLength(255),
                        TextInput::make('telefono')->label('Teléfono / WhatsApp')->required()
                            ->tel()->rules(['regex:/^[0-9+\s-]{9,20}$/'])
                            ->validationMessages(['regex' => 'Ingresa un teléfono válido.']),
                        TextInput::make('documento_cliente')->label('DNI / RUC')
                            ->placeholder('Opcional')
                            ->nullable()
                            ->numeric()
                            ->rules(fn (Get $get): array => $get('requiere_factura')
                                ? ['required', 'regex:/^\d{11}$/']
                                : ['nullable', 'regex:/^(?:\d{8}|\d{11})$/'])
                            ->validationMessages([
                                'required' => 'Ingresa el RUC para emitir la factura.',
                                'regex' => 'Ingresa un DNI de 8 dígitos o un RUC válido de 11 dígitos.',
                            ]),
                        Toggle::make('requiere_factura')
                            ->label('Desea factura')
                            ->helperText('Activa esta opción si el servicio debe pasar por facturación.')
                            ->default(false)
                            ->inline(false)
                            ->live(),
                        Select::make('tipo_atencion')
                            ->label('¿Qué necesita el equipo?')
                            ->options([
                                'mantenimiento' => 'Mantenimiento',
                                'reparacion' => 'Reparación',
                            ])
                            ->helperText('Esta elección definirá automáticamente la etapa de trabajo.')
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Select::make('tipo_equipo')->label('Tipo de equipo')->options(self::tiposEquipo())
                            ->searchable()->native(false)->required(),
                        ...self::camposReferenciaProveedor(),
                        TextInput::make('modelo')->label('Modelo')->placeholder('Opcional'),
                        TextInput::make('numero_serie')->label('Número de serie')->placeholder('Opcional'),
                        Select::make('condicion_visible')->label('Condición visible')->options([
                            'normal' => 'Normal', 'sucio' => 'Sucio', 'golpeado' => 'Golpeado',
                            'incompleto' => 'Incompleto', 'desarmado' => 'Desarmado',
                        ])->default('normal')->native(false)->required(),
                    ]),
                    Textarea::make('falla_reportada')->label('Falla comunicada por el cliente')
                        ->placeholder('Ejemplo: enciende, pero se apaga al acelerar.')
                        ->required()->minLength(10)->rows(3)->columnSpanFull(),
                    TextInput::make('accesorios')->label('Accesorios entregados')
                        ->placeholder('Ejemplo: cargador, llave, manguera o ninguno')->columnSpanFull(),
                ]),
            Section::make('Recepción')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Select::make('prioridad')->label('Prioridad')->options([
                            'normal' => 'Normal', 'urgente' => 'Urgente',
                        ])->default('normal')->native(false)->required(),
                        DatePicker::make('fecha_solicitada')->label('Fecha solicitada')->minDate(today())->native(false),
                    ]),
                    FileUpload::make('archivos_temporales')->label('Fotografías del equipo')
                        ->helperText('Recomendado: 2 a 5 fotos. Puedes guardar sin fotografías.')
                        ->image()->multiple()->maxFiles(5)->maxSize(15360)
                        ->disk('local')->directory('servicio-tecnico/temporales')
                        ->preserveFilenames()->imagePreviewHeight('160')->columnSpanFull(),
                ]),
        ]);
    }

    public static function tiposEquipo(): array
    {
        return [
            'motoguadana' => 'Motoguadaña',
            'bomba_agua' => 'Bomba de agua',
            'motor' => 'Motor',
            'herramienta' => 'Herramienta eléctrica',
            'combustion' => 'Máquina a combustión',
            'maquina_electrica' => 'Máquina eléctrica',
            'mecanizado' => 'Trabajo de torno o fresado',
            'otro' => 'Otro',
        ];
    }

    public static function camposReferenciaProveedor(bool $categoriaRequerida = true): array
    {
        return [
            Select::make('categoria_servicio_tecnico_id')
                ->label('Categoría técnica')
                ->helperText('Grupo general del equipo o insumo recibido.')
                ->relationship(
                    'categoriaServicioTecnico',
                    'nombre',
                    modifyQueryUsing: fn ($query) => $query->where('activo', true)->orderBy('orden'),
                )
                ->searchable()
                ->preload()
                ->live()
                ->required($categoriaRequerida)
                ->afterStateUpdated(fn (Set $set) => $set('proveedor_servicio_tecnico_id', null)),
            Select::make('marca_servicio_tecnico_id')
                ->label('Marca')
                ->helperText('Opcional si la máquina no tiene marca visible.')
                ->relationship(
                    'marcaServicioTecnico',
                    'nombre',
                    modifyQueryUsing: fn ($query) => $query->where('activo', true)->orderBy('nombre'),
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('proveedor_servicio_tecnico_id', null)),
            Select::make('proveedor_servicio_tecnico_id')
                ->label('Proveedor relacionado')
                ->helperText('Opcional. Se filtra según la categoría y marca elegidas.')
                ->options(function (Get $get): array {
                    $query = ProveedorServicioTecnico::query()
                        ->whereIn('estado', ['evaluacion', 'activo']);

                    if (filled($categoria = $get('categoria_servicio_tecnico_id'))) {
                        $query->whereHas('categorias', fn ($relacion) => $relacion->whereKey($categoria));
                    }

                    if (filled($marca = $get('marca_servicio_tecnico_id'))) {
                        $query->whereHas('marcas', fn ($relacion) => $relacion->whereKey($marca));
                    }

                    return $query->orderBy('razon_social')->pluck('razon_social', 'id')->all();
                })
                ->searchable()
                ->native(false)
                ->disabled(fn (Get $get): bool => blank($get('categoria_servicio_tecnico_id')))
                ->columnSpanFull(),
        ];
    }
}
