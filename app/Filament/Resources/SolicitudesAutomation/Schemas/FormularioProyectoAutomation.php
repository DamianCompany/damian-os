<?php

namespace App\Filament\Resources\SolicitudesAutomation\Schemas;

use Filament\Forms\Components\DatePicker;
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

class FormularioProyectoAutomation
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                self::alcance(),
                self::cotizacion(),
                self::proyecto(),
                self::materiales(),
                self::pruebas(),
                self::entrega(),
            ])->skippable()->columnSpanFull(),
        ]);
    }

    private static function recalcular(Get $get, Set $set): void
    {
        $costo = (float) $get('costo_estimado');
        $contingencia = $costo * ((float) $get('contingencia_porcentaje') / 100);
        $subtotal = $costo + $contingencia;
        $precio = $subtotal * (1 + ((float) $get('margen_porcentaje') / 100));
        $set('precio_venta', round($precio, 2));
    }

    private static function alcance(): Step
    {
        return Step::make('Alcance')
            ->icon('heroicon-o-document-text')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Objetivo y límites')
                    ->description('Define lo que debe lograr la solución y qué queda fuera.')
                    ->schema([
                        Select::make('tipo_servicio')
                            ->label('Tipo de servicio')
                            ->options(FormularioSolicitudAutomation::tiposServicio())
                            ->native(false)->live()->required(),
                        Textarea::make('objetivo')->label('Objetivo del proyecto')->rows(2)->required()->columnSpanFull(),
                        Textarea::make('proceso_actual')->label('Proceso actual')->rows(3)->columnSpanFull(),
                        Repeater::make('alcance_incluido')
                            ->label('Alcance incluido')
                            ->schema([TextInput::make('elemento')->label('Función, módulo o componente')->required()])
                            ->addActionLabel('Agregar al alcance')->defaultItems(0)->columnSpanFull(),
                        Repeater::make('exclusiones')
                            ->label('No incluido')
                            ->schema([TextInput::make('elemento')->label('Exclusión')->required()])
                            ->addActionLabel('Agregar exclusión')->defaultItems(0)->columnSpanFull(),
                        Repeater::make('entregables')
                            ->label('Entregables')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])->schema([
                                    Select::make('tipo')->label('Tipo')->options([
                                        'maquina' => 'Máquina', 'tablero' => 'Tablero', 'codigo' => 'Código o programa',
                                        'aplicacion' => 'Aplicación', 'planos' => 'Planos', 'manual' => 'Manual',
                                        'capacitacion' => 'Capacitación', 'otro' => 'Otro',
                                    ])->native(false)->required(),
                                    TextInput::make('nombre')->label('Entregable')->required(),
                                    Select::make('estado')->label('Estado')->options([
                                        'pendiente' => 'Pendiente', 'en_revision' => 'En revisión',
                                        'aprobado' => 'Aprobado', 'entregado' => 'Entregado',
                                    ])->default('pendiente')->native(false),
                                ]),
                            ])->addActionLabel('Agregar entregable')->defaultItems(0)->columnSpanFull(),
                        Textarea::make('criterios_aceptacion')->label('Criterios de aceptación')->rows(3)->required()->columnSpanFull(),
                        Textarea::make('tecnologia_propuesta')->label('Tecnología propuesta')->rows(2),
                        Textarea::make('restricciones')->label('Restricciones y riesgos iniciales')->rows(2),
                    ]),
                Section::make('Requerimientos técnicos')
                    ->description('El contenido cambia según el servicio seleccionado.')
                    ->schema([
                        Textarea::make('requerimientos_tecnicos.plc')
                            ->label('Entradas, salidas, sensores, actuadores, motores, HMI, protocolos y seguridad')
                            ->visible(fn (Get $get): bool => in_array($get('tipo_servicio'), ['automatizacion', 'plc_hmi', 'tablero'], true))
                            ->rows(5),
                        Textarea::make('requerimientos_tecnicos.maquina')
                            ->label('Producto, capacidad, velocidad, dimensiones, precisión, energía y condiciones')
                            ->visible(fn (Get $get): bool => $get('tipo_servicio') === 'maquina')
                            ->rows(5),
                        Textarea::make('requerimientos_tecnicos.software')
                            ->label('Usuarios, roles, módulos, reportes, integraciones, archivos y dispositivos')
                            ->visible(fn (Get $get): bool => in_array($get('tipo_servicio'), ['web', 'app'], true))
                            ->rows(5),
                        Textarea::make('requerimientos_tecnicos.iot')
                            ->label('Dispositivos, sensores, frecuencia, comunicación, energía, alertas y plataforma')
                            ->visible(fn (Get $get): bool => $get('tipo_servicio') === 'iot')
                            ->rows(5),
                        Textarea::make('requerimientos_tecnicos.ia')
                            ->label('Problema, datos, variable objetivo, métrica y forma de uso')
                            ->visible(fn (Get $get): bool => $get('tipo_servicio') === 'inteligencia_artificial')
                            ->rows(5),
                        Textarea::make('requerimientos_tecnicos.otro')
                            ->label('Información técnica complementaria')
                            ->visible(fn (Get $get): bool => $get('tipo_servicio') === 'otro')
                            ->rows(5),
                    ]),
            ]);
    }

    private static function cotizacion(): Step
    {
        return Step::make('Cotización')
            ->icon('heroicon-o-banknotes')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Factibilidad y actividades')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            Select::make('factibilidad')->label('Resultado de factibilidad')->options([
                                'viable' => 'Viable', 'viable_condiciones' => 'Viable con condiciones',
                                'requiere_prototipo' => 'Requiere prototipo', 'falta_informacion' => 'Falta información',
                                'no_viable' => 'No viable',
                            ])->native(false),
                            Select::make('estado')->label('Estado')->options(\App\Filament\Resources\SolicitudesAutomation\Tables\TablaSolicitudesAutomation::estados())->native(false)->required(),
                        ]),
                        Repeater::make('actividades')
                            ->label('Actividades cotizadas')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 4])->schema([
                                    TextInput::make('actividad')->label('Actividad')->required()->columnSpan(2),
                                    TextInput::make('horas')->label('Horas')->numeric()->minValue(0),
                                    TextInput::make('tarifa')->label('Tarifa/hora')->prefix('S/')->numeric()->minValue(0),
                                ]),
                            ])->addActionLabel('Agregar actividad')->defaultItems(0)->columnSpanFull(),
                    ]),
                Section::make('Cálculo comercial')
                    ->description('El precio final se actualiza con costo, contingencia y margen.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                            TextInput::make('costo_estimado')->label('Costo estimado')->prefix('S/')->numeric()->minValue(0)->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                            TextInput::make('contingencia_porcentaje')->label('Contingencia')->suffix('%')->numeric()->minValue(0)->maxValue(100)->default(10)->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                            TextInput::make('margen_porcentaje')->label('Margen')->suffix('%')->numeric()->minValue(0)->default(25)->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),
                            TextInput::make('precio_venta')->label('Precio de venta')->prefix('S/')->numeric()->disabled()->dehydrated(),
                        ]),
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            Select::make('forma_pago')->label('Forma de pago')->options([
                                'adelanto' => 'Adelanto y saldo', 'hitos' => 'Pagos por hitos',
                                'contra_entrega' => 'Contra entrega', 'otro' => 'Otro',
                            ])->native(false),
                            DatePicker::make('fecha_inicio')->label('Inicio propuesto')->native(false),
                            DatePicker::make('fecha_fin_estimada')->label('Fin estimado')->native(false)->afterOrEqual('fecha_inicio'),
                        ]),
                    ]),
            ]);
    }

    private static function proyecto(): Step
    {
        return Step::make('Proyecto')
            ->icon('heroicon-o-wrench-screwdriver')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Aprobación y ejecución')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            DatePicker::make('fecha_aprobacion')->label('Fecha de aprobación')->native(false),
                            TextInput::make('canal_aprobacion')->label('Canal de aprobación')->placeholder('Correo, reunión, orden de compra...'),
                            Select::make('prioridad')->label('Prioridad')->options(['normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'])->native(false),
                        ]),
                        Textarea::make('evidencia_aprobacion')->label('Evidencia o referencia de aprobación')->rows(2)->columnSpanFull(),
                        Repeater::make('tareas')->label('Tareas y avances')->schema([
                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                TextInput::make('tarea')->label('Tarea')->required()->columnSpan(2),
                                Select::make('estado')->label('Estado')->options([
                                    'pendiente' => 'Pendiente', 'en_ejecucion' => 'En ejecución',
                                    'pausada' => 'Pausada', 'bloqueada' => 'Bloqueada', 'finalizada' => 'Finalizada',
                                ])->default('pendiente')->native(false),
                                TextInput::make('avance')->label('Avance')->suffix('%')->numeric()->minValue(0)->maxValue(100),
                            ]),
                            Textarea::make('resultado')->label('Resultado o comentario')->rows(2),
                        ])->addActionLabel('Agregar tarea')->defaultItems(0)->columnSpanFull(),
                        Repeater::make('cambios')->label('Solicitudes de cambio')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('cambio')->label('Cambio solicitado')->required(),
                                TextInput::make('impacto')->label('Impacto en costo o plazo'),
                                Select::make('decision')->label('Decisión')->options([
                                    'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado',
                                ])->default('pendiente')->native(false),
                            ]),
                        ])->addActionLabel('Registrar cambio')->defaultItems(0)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function materiales(): Step
    {
        return Step::make('Materiales')
            ->icon('heroicon-o-cube')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Lista de materiales y compras')
                    ->schema([
                        Repeater::make('materiales')->hiddenLabel()->schema([
                            Grid::make(['default' => 1, 'md' => 5])->schema([
                                TextInput::make('codigo')->label('Código'),
                                TextInput::make('descripcion')->label('Componente o material')->required()->columnSpan(2),
                                TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0)->required(),
                                TextInput::make('costo')->label('Costo')->prefix('S/')->numeric()->minValue(0),
                            ]),
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                Select::make('estado')->label('Estado')->options([
                                    'por_cotizar' => 'Por cotizar', 'por_comprar' => 'Por comprar',
                                    'comprado' => 'Comprado', 'recibido' => 'Recibido', 'consumido' => 'Consumido',
                                ])->default('por_cotizar')->native(false),
                                TextInput::make('proveedor')->label('Proveedor'),
                                TextInput::make('alternativa')->label('Alternativa'),
                            ]),
                        ])->addActionLabel('Agregar material')->defaultItems(0)->columnSpanFull(),
                        Repeater::make('servicios_externos')->label('Servicios externos')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('servicio')->label('Servicio')->required(),
                                TextInput::make('proveedor')->label('Proveedor'),
                                TextInput::make('costo')->label('Costo')->prefix('S/')->numeric()->minValue(0),
                            ]),
                        ])->addActionLabel('Agregar servicio')->defaultItems(0)->columnSpanFull(),
                        TextInput::make('costo_real')->label('Costo real acumulado')->prefix('S/')->numeric()->minValue(0),
                    ]),
            ]);
    }

    private static function pruebas(): Step
    {
        return Step::make('Pruebas')
            ->icon('heroicon-o-clipboard-document-check')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Pruebas e instalación')
                    ->schema([
                        Repeater::make('pruebas')->hiddenLabel()->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                Select::make('tipo')->label('Tipo de prueba')->options([
                                    'fat' => 'FAT', 'sat' => 'SAT', 'uat' => 'UAT',
                                    'electrica' => 'Eléctrica', 'funcional' => 'Funcional', 'otra' => 'Otra',
                                ])->native(false)->required(),
                                TextInput::make('criterio')->label('Criterio')->required(),
                                Select::make('resultado')->label('Resultado')->options([
                                    'pendiente' => 'Pendiente', 'conforme' => 'Conforme',
                                    'observado' => 'Observado', 'repetir' => 'Repetir',
                                ])->default('pendiente')->native(false),
                            ]),
                            Textarea::make('observacion')->label('Observación y evidencia')->rows(2),
                        ])->addActionLabel('Registrar prueba')->defaultItems(0)->columnSpanFull(),
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            DatePicker::make('fecha_instalacion')->label('Fecha de instalación o despliegue')->native(false),
                            TextInput::make('lugar_instalacion')->label('Lugar de instalación'),
                        ]),
                        Textarea::make('observacion_instalacion')->label('Observaciones de instalación')->rows(3)->columnSpanFull(),
                        Repeater::make('capacitaciones')->label('Capacitaciones')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('tema')->label('Tema')->required(),
                                TextInput::make('participantes')->label('Participantes'),
                                TextInput::make('duracion')->label('Duración'),
                            ]),
                        ])->addActionLabel('Agregar capacitación')->defaultItems(0)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function entrega(): Step
    {
        return Step::make('Entrega')
            ->icon('heroicon-o-truck')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Entrega, garantía y soporte')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            DatePicker::make('fecha_entrega')->label('Fecha de entrega')->native(false),
                            TextInput::make('garantia_dias')->label('Garantía')->suffix('días')->numeric()->minValue(0),
                            Select::make('estado')->label('Estado final')->options(\App\Filament\Resources\SolicitudesAutomation\Tables\TablaSolicitudesAutomation::estados())->native(false)->required(),
                        ]),
                        Textarea::make('observaciones_entrega')->label('Observaciones y acta de entrega')->rows(3)->columnSpanFull(),
                        Repeater::make('incidencias_soporte')->label('Incidencias de soporte')->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                TextInput::make('incidencia')->label('Incidencia')->required(),
                                Select::make('prioridad')->label('Prioridad')->options([
                                    'baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica',
                                ])->default('media')->native(false),
                                Select::make('estado')->label('Estado')->options([
                                    'abierta' => 'Abierta', 'en_atencion' => 'En atención', 'resuelta' => 'Resuelta',
                                ])->default('abierta')->native(false),
                            ]),
                            Textarea::make('solucion')->label('Atención o solución')->rows(2),
                        ])->addActionLabel('Registrar incidencia')->defaultItems(0)->columnSpanFull(),
                    ]),
            ]);
    }
}
