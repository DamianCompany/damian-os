<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class FormularioTrabajoServicioTecnico
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('tipo_atencion'),
            Wizard::make([
                self::diagnostico(),
                self::reparacion(),
                self::entrega(),
            ])->skippable()->columnSpanFull(),
        ]);
    }

    private static function recalcular(Get $get, Set $set): void
    {
        if ($get('tipo_atencion') === 'mantenimiento') {
            $total = self::sumarLineas($get('conceptos_mantenimiento'), 'cantidad', 'precio_unitario');
        } else {
            $total = self::sumarLineas($get('repuestos'), 'cantidad', 'costo')
                + collect($get('mano_obra') ?? [])->sum(
                    fn (array $linea): float => isset($linea['monto'])
                        ? (float) $linea['monto']
                        : (float) ($linea['horas'] ?? 0) * (float) ($linea['tarifa'] ?? 0),
                )
                + self::sumarLineas($get('servicios_externos'), null, 'costo');
        }

        $total = round($total, 2);
        $base = round($total / 1.18, 2);
        $set('costo_estimado', $total);
        $set('precio_cotizado', $total);
        $set('base_imponible', $base);
        $set('igv_incluido', round($total - $base, 2));
    }

    private static function sumarLineas(?array $lineas, ?string $cantidad, string $precio): float
    {
        return collect($lineas ?? [])->sum(
            fn (array $linea): float => ($cantidad ? (float) ($linea[$cantidad] ?? 0) : 1)
                * (float) ($linea[$precio] ?? 0),
        );
    }

    private static function diagnostico(): Step
    {
        return Step::make('Diagnóstico y cotización')
            ->icon('heroicon-o-magnifying-glass')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Diagnóstico')
                    ->description('Registra solamente lo encontrado y la solución propuesta.')
                    ->schema([
                        Repeater::make('checklist_diagnostico')->label('Componentes revisados')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('componente')->label('Componente')->required(),
                                    Select::make('resultado')->label('Resultado')->options([
                                        'correcto' => 'Correcto', 'observado' => 'Observado',
                                        'defectuoso' => 'Defectuoso', 'no_aplica' => 'No aplica',
                                    ])->native(false)->required(),
                                ]),
                            ])->addActionLabel('Agregar componente')->defaultItems(0)->columnSpanFull(),
                        Textarea::make('diagnostico')->label('Diagnóstico breve')->rows(3)->columnSpanFull(),
                        Textarea::make('solucion_recomendada')->label('Solución recomendada')->rows(3)->columnSpanFull(),
                        Select::make('resultado_tecnico')->label('Resultado técnico')->options([
                            'reparable' => 'Reparable',
                            'reparable_repuesto' => 'Reparable con repuesto',
                            'fabricar_pieza' => 'Requiere fabricar pieza',
                            'no_reparable' => 'No reparable',
                            'pendiente' => 'Pendiente de información',
                        ])->native(false),
                    ]),
                Section::make('Costos y cotización')
                    ->description(fn (Get $get): string => $get('tipo_atencion') === 'mantenimiento'
                        ? 'Cotiza directamente los servicios de mantenimiento.'
                        : 'La cotización se calcula con repuestos, mano de obra y servicios externos.')
                    ->schema([
                        Repeater::make('conceptos_mantenimiento')->label('Servicios de mantenimiento')
                            ->visible(fn (Get $get): bool => $get('tipo_atencion') === 'mantenimiento')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 4])->schema([
                                    TextInput::make('descripcion')->label('Descripción')->required()->columnSpan(2),
                                    TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.01)->default(1)->required(),
                                    TextInput::make('precio_unitario')->label('Precio')->prefix('S/')->numeric()->minValue(0)->required(),
                                ]),
                            ])->addActionLabel('Agregar servicio')->defaultItems(1)->columnSpanFull()
                            ->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                        Repeater::make('repuestos')->label('Repuestos y materiales')->schema([
                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                TextInput::make('descripcion')->label('Repuesto')->required()->columnSpan(2),
                                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0)->required(),
                                TextInput::make('costo')->label('Costo')->prefix('S/')->numeric()->minValue(0),
                            ]),
                            Select::make('disponibilidad')->label('Disponibilidad')->options([
                                'disponible' => 'Disponible', 'por_comprar' => 'Por comprar', 'sin_stock' => 'Sin stock',
                            ])->native(false),
                        ])->addActionLabel('Agregar repuesto')->defaultItems(0)->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('tipo_atencion') === 'reparacion')
                            ->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                        Repeater::make('mano_obra')->label('Mano de obra')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('actividad')->label('Actividad realizada')->required()->columnSpan(2),
                                TextInput::make('monto')->label('Monto')->prefix('S/')->numeric()->minValue(0)->required(),
                            ]),
                        ])->addActionLabel('Agregar actividad')->defaultItems(0)->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('tipo_atencion') === 'reparacion')
                            ->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                        Repeater::make('servicios_externos')->label('Servicios externos')->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                TextInput::make('descripcion')->label('Servicio')->required(),
                                TextInput::make('costo')->label('Costo')->prefix('S/')->numeric()->minValue(0),
                            ]),
                        ])->addActionLabel('Agregar servicio externo')->defaultItems(0)->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('tipo_atencion') === 'reparacion')
                            ->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                        Grid::make(['default' => 1, 'md' => 4])->schema([
                            TextInput::make('base_imponible')->label('Valor sin IGV')->prefix('S/')->disabled()->dehydrated(),
                            TextInput::make('igv_incluido')->label('IGV incluido (18%)')->prefix('S/')->disabled()->dehydrated(),
                            TextInput::make('precio_cotizado')->label('Total a pagar')->prefix('S/')->disabled()->dehydrated(),
                            DatePicker::make('fecha_entrega_estimada')->label('Entrega estimada')->native(false),
                        ]),
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            Select::make('decision_cliente')->label('Decisión del cliente')->options([
                                'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada',
                                'rechazada' => 'Rechazada', 'requiere_cambio' => 'Requiere cambio',
                            ])->native(false),
                            Select::make('canal_aprobacion')->label('Canal')->options([
                                'whatsapp' => 'WhatsApp', 'presencial' => 'Presencial',
                                'correo' => 'Correo', 'otro' => 'Otro',
                            ])->native(false),
                            DateTimePicker::make('fecha_aprobacion')->label('Fecha de aprobación')->native(false)->seconds(false),
                        ]),
                        Textarea::make('evidencia_aprobacion')->label('Evidencia o comentario de aprobación')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function reparacion(): Step
    {
        return Step::make('Mantenimiento / Reparación')
            ->label(fn (Get $get): string => $get('tipo_atencion') === 'mantenimiento'
                ? 'Mantenimiento'
                : 'Reparación')
            ->icon('heroicon-o-wrench')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make(fn (Get $get): string => $get('tipo_atencion') === 'mantenimiento'
                    ? 'Mantenimiento realizado'
                    : 'Reparación realizada')
                    ->description('Puedes iniciar, pausar o terminar desde los botones rápidos de la ficha.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            DateTimePicker::make('trabajo_iniciado_en')->label('Inicio real')->native(false)->seconds(false),
                            DateTimePicker::make('trabajo_finalizado_en')->label('Fin real')->native(false)->seconds(false),
                            TextInput::make('tiempo_real_minutos')->label('Tiempo real')->suffix('min')->numeric()->minValue(0),
                        ]),
                        Textarea::make('trabajo_realizado')->label('Descripción de lo realizado')->rows(4)->columnSpanFull(),
                        Repeater::make('material_usado')->label('Material realmente utilizado')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('descripcion')->label('Material')->required(),
                                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0),
                                TextInput::make('costo')->label('Costo real')->prefix('S/')->numeric()->minValue(0),
                            ]),
                        ])->addActionLabel('Agregar material usado')->defaultItems(0)->columnSpanFull(),
                        TextInput::make('costo_real')->label('Costo real total')->prefix('S/')->numeric()->minValue(0),
                        TextInput::make('ubicacion_fisica')->label('Ubicación física')->placeholder('Ejemplo: Taller, Estante A-02'),
                    ]),
            ]);
    }

    private static function entrega(): Step
    {
        return Step::make('Prueba y entrega')
            ->icon('heroicon-o-check-badge')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Prueba')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            Select::make('resultado_prueba')->label('Resultado')->options([
                                'conforme' => 'Conforme', 'observado' => 'Observado', 'no_reparable' => 'No reparable',
                            ])->native(false),
                            TextInput::make('duracion_prueba_minutos')->label('Duración')->suffix('min')->numeric()->minValue(0),
                        ]),
                        Textarea::make('observacion_prueba')->label('Observación de prueba')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Entrega y garantía')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            Select::make('aviso_cliente_canal')->label('Aviso al cliente')->options([
                                'whatsapp' => 'WhatsApp', 'llamada' => 'Llamada', 'correo' => 'Correo', 'presencial' => 'Presencial',
                            ])->native(false),
                            TextInput::make('persona_recoge')->label('Persona que recoge'),
                            TextInput::make('documento_recoge')->label('Documento')->nullable()
                                ->rules(['nullable', 'regex:/^(?:\d{8}|\d{11})$/']),
                            Select::make('estado_pago')->label('Pago')->options([
                                'pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'pagado' => 'Pagado',
                            ])->default('pendiente')->native(false),
                            DateTimePicker::make('entregado_en')->label('Fecha de entrega')->native(false)->seconds(false),
                            TextInput::make('garantia_dias')->label('Garantía')->suffix('días')->numeric()->minValue(0),
                        ]),
                        Toggle::make('repuestos_antiguos_entregados')->label('Repuestos antiguos entregados al cliente'),
                    ]),
                Section::make('Retorno o garantía')
                    ->description('Completa únicamente si el equipo regresó después de la entrega.')
                    ->collapsed()
                    ->schema([
                        Textarea::make('motivo_retorno')->label('Motivo del retorno')->rows(2),
                        Select::make('clasificacion_retorno')->label('Clasificación')->options([
                            'garantia' => 'Garantía', 'nueva_falla' => 'Nueva falla', 'mal_uso' => 'Mal uso',
                        ])->native(false),
                        Textarea::make('solucion_retorno')->label('Solución aplicada')->rows(3),
                    ]),
            ]);
    }
}
