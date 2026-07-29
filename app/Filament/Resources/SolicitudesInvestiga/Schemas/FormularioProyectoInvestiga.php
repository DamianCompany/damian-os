<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class FormularioProyectoInvestiga
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                self::definicion(),
                self::planificacion(),
                self::datos(),
                self::experimentos(),
                self::resultados(),
            ])
                ->skippable()
                ->columnSpanFull(),
        ]);
    }

    private static function definicion(): Step
    {
        return Step::make('Definición')
            ->icon('heroicon-o-light-bulb')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Definir el proyecto')
                    ->description('Aclara qué se resolverá y cómo se comprobará el resultado.')
                    ->schema([
                        Select::make('tipo_proyecto')
                            ->label('Tipo de proyecto')
                            ->options([
                                'investigacion' => 'Investigación',
                                'innovacion' => 'Innovación',
                                'prototipo' => 'Prototipo',
                                'inteligencia_artificial' => 'Inteligencia artificial',
                                'analisis_datos' => 'Análisis de datos',
                                'capacitacion' => 'Capacitación',
                                'otro' => 'Otro',
                            ])
                            ->native(false)
                            ->live()
                            ->required(),
                        Textarea::make('problema_necesidad')
                            ->label('Problema definido')
                            ->required()
                            ->rows(3)
                            ->maxLength(3000)
                            ->columnSpanFull(),
                        TextInput::make('pregunta_principal')
                            ->label('Pregunta principal')
                            ->placeholder('Opcional para proyectos de investigación')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('objetivo_general')
                            ->label('Objetivo general')
                            ->required()
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Repeater::make('objetivos_especificos')
                            ->label('Objetivos específicos')
                            ->helperText('Puedes guardarlos después. Para completar la definición se recomiendan entre 2 y 5 objetivos.')
                            ->schema([
                                TextInput::make('objetivo')
                                    ->label('Objetivo')
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->maxItems(5)
                            ->defaultItems(0)
                            ->addActionLabel('Agregar objetivo')
                            ->reorderable()
                            ->columnSpanFull(),
                        Grid::make(['default' => 1, 'lg' => 2])->schema([
                            Textarea::make('beneficiarios')
                                ->label('Beneficiarios o usuario')
                                ->rows(3)
                                ->required(),
                            Textarea::make('alcance')
                                ->label('Alcance y límites')
                                ->rows(3)
                                ->required(),
                        ]),
                        Textarea::make('criterios_exito')
                            ->label('Criterios de éxito')
                            ->placeholder('Indicadores o condiciones que demostrarán que el proyecto funcionó')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Información especializada')
                    ->description('Solo se muestran los datos correspondientes al tipo de proyecto.')
                    ->schema([
                        Textarea::make('definicion_especializada.metodologia')
                            ->label('Enfoque, diseño y metodología')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'investigacion')
                            ->rows(4),
                        Textarea::make('definicion_especializada.datos_ia')
                            ->label('Datos, algoritmo, métricas y forma de uso')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'inteligencia_artificial')
                            ->rows(4),
                        Textarea::make('definicion_especializada.analisis_datos')
                            ->label('Fuente, variables, periodo y limpieza esperada')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'analisis_datos')
                            ->rows(4),
                        Textarea::make('definicion_especializada.prototipo')
                            ->label('Función, componentes, dimensiones, materiales y pruebas')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'prototipo')
                            ->rows(4),
                        Textarea::make('definicion_especializada.capacitacion')
                            ->label('Tema, público, cupos, duración, modalidad y evaluación')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'capacitacion')
                            ->rows(4),
                        Textarea::make('definicion_especializada.innovacion')
                            ->label('Propuesta innovadora, diferencia y forma de validación')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'innovacion')
                            ->rows(4),
                        Textarea::make('definicion_especializada.otro')
                            ->label('Información técnica complementaria')
                            ->visible(fn (Get $get): bool => $get('tipo_proyecto') === 'otro')
                            ->rows(4),
                    ]),
            ]);
    }

    private static function planificacion(): Step
    {
        return Step::make('Planificación')
            ->icon('heroicon-o-calendar-days')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Factibilidad y método')
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 2])->schema([
                            Select::make('factibilidad')
                                ->label('Factibilidad')
                                ->options([
                                    'viable' => 'Viable',
                                    'viable_condiciones' => 'Viable con condiciones',
                                    'requiere_piloto' => 'Requiere piloto',
                                    'falta_informacion' => 'Falta información',
                                    'no_viable' => 'No viable',
                                ])
                                ->native(false)
                                ->required(),
                            Select::make('estado')
                                ->label('Estado del proyecto')
                                ->options(self::estados())
                                ->native(false)
                                ->required(),
                        ]),
                        Textarea::make('metodologia_resumida')
                            ->label('Metodología resumida')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Actividades y recursos')
                    ->schema([
                        Repeater::make('actividades')
                            ->label('Plan de actividades')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 4])->schema([
                                    TextInput::make('actividad')->label('Actividad')->required()->columnSpan(2),
                                    DatePicker::make('fecha')->label('Fecha')->native(false),
                                    Select::make('estado')
                                        ->label('Estado')
                                        ->options([
                                            'pendiente' => 'Pendiente',
                                            'en_proceso' => 'En proceso',
                                            'completada' => 'Completada',
                                            'bloqueada' => 'Bloqueada',
                                        ])
                                        ->default('pendiente')
                                        ->native(false),
                                ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Agregar actividad')
                            ->columnSpanFull(),
                        Repeater::make('recursos')
                            ->label('Recursos')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])->schema([
                                    Select::make('tipo')
                                        ->label('Tipo')
                                        ->options([
                                            'equipo' => 'Equipo',
                                            'software' => 'Software',
                                            'datos' => 'Datos',
                                            'material' => 'Material',
                                            'servicio' => 'Servicio',
                                        ])
                                        ->native(false)
                                        ->required(),
                                    TextInput::make('nombre')->label('Recurso')->required(),
                                    TextInput::make('costo')->label('Costo')->prefix('S/')->numeric()->minValue(0),
                                ]),
                            ])
                            ->addActionLabel('Agregar recurso')
                            ->columnSpanFull(),
                    ]),
                Section::make('Cronograma, presupuesto y riesgos')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                            DatePicker::make('fecha_inicio')->label('Inicio')->native(false),
                            DatePicker::make('fecha_fin_estimada')->label('Fin estimado')->native(false)->afterOrEqual('fecha_inicio'),
                            TextInput::make('presupuesto_estimado')->label('Presupuesto estimado')->prefix('S/')->numeric()->minValue(0),
                            TextInput::make('contingencia')->label('Contingencia')->prefix('S/')->numeric()->minValue(0),
                        ]),
                        Repeater::make('riesgos')
                            ->label('Riesgos')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])->schema([
                                    TextInput::make('riesgo')->label('Riesgo')->required(),
                                    Select::make('nivel')
                                        ->label('Nivel')
                                        ->options(['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto'])
                                        ->native(false),
                                    TextInput::make('respuesta')->label('Acción preventiva'),
                                ]),
                            ])
                            ->addActionLabel('Agregar riesgo')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function datos(): Step
    {
        return Step::make('Datos')
            ->icon('heroicon-o-circle-stack')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Datasets y fuentes')
                    ->description('Registra el original y sus versiones sin reemplazar la evidencia.')
                    ->schema([
                        Repeater::make('datasets')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('nombre')->label('Nombre del dataset')->required(),
                                    TextInput::make('fuente')->label('Fuente')->required(),
                                    TextInput::make('ubicacion')->label('Archivo o enlace seguro')->url(),
                                    Select::make('permiso')
                                        ->label('Permiso o licencia')
                                        ->options([
                                            'propio' => 'Propio',
                                            'autorizado' => 'Autorizado',
                                            'publico' => 'Público',
                                            'restringido' => 'Restringido',
                                        ])
                                        ->native(false),
                                    Toggle::make('datos_personales')->label('Contiene datos personales'),
                                    Select::make('calidad')
                                        ->label('Calidad')
                                        ->options([
                                            'sin_revisar' => 'Sin revisar',
                                            'observada' => 'Con observaciones',
                                            'limpia' => 'Validada / limpia',
                                        ])
                                        ->default('sin_revisar')
                                        ->native(false),
                                ]),
                                Textarea::make('diccionario')
                                    ->label('Variables o diccionario resumido')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Registrar dataset')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function experimentos(): Step
    {
        return Step::make('Experimentos')
            ->icon('heroicon-o-beaker')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Pruebas, prototipos y modelos')
                    ->description('Usa únicamente el tipo que corresponda al proyecto.')
                    ->schema([
                        Repeater::make('experimentos')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])->schema([
                                    Select::make('tipo')
                                        ->label('Tipo')
                                        ->options([
                                            'experimento' => 'Experimento',
                                            'prototipo' => 'Prototipo',
                                            'modelo_ia' => 'Modelo de IA',
                                            'prueba' => 'Prueba o validación',
                                        ])
                                        ->native(false)
                                        ->required(),
                                    TextInput::make('nombre')->label('Nombre o versión')->required(),
                                    Select::make('estado')
                                        ->label('Estado')
                                        ->options([
                                            'programado' => 'Programado',
                                            'en_ejecucion' => 'En ejecución',
                                            'observado' => 'Observado',
                                            'validado' => 'Validado',
                                        ])
                                        ->default('programado')
                                        ->native(false),
                                ]),
                                Textarea::make('objetivo')->label('Objetivo')->rows(2)->required(),
                                Textarea::make('procedimiento')->label('Procedimiento, condiciones y parámetros')->rows(3),
                                Textarea::make('resultado')->label('Resultado, métricas y conclusión')->rows(3),
                                TextInput::make('evidencia')->label('Enlace a evidencia o repositorio')->url(),
                            ])
                            ->addActionLabel('Agregar experimento o prueba')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function resultados(): Step
    {
        return Step::make('Resultados')
            ->icon('heroicon-o-document-check')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                Section::make('Análisis y conclusión')
                    ->schema([
                        Select::make('metodo_analisis')
                            ->label('Método de análisis')
                            ->options([
                                'estadistico' => 'Estadístico',
                                'geoespacial' => 'Geoespacial',
                                'cualitativo' => 'Cualitativo',
                                'visual' => 'Visual',
                                'inteligencia_artificial' => 'Inteligencia artificial',
                                'otro' => 'Otro',
                            ])
                            ->native(false),
                        Textarea::make('resultado_principal')
                            ->label('Resultado principal')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('limitaciones')
                            ->label('Limitaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Entregables y cierre')
                    ->schema([
                        Repeater::make('entregables')
                            ->label('Entregables')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])->schema([
                                    Select::make('tipo')
                                        ->label('Tipo')
                                        ->options([
                                            'informe' => 'Informe',
                                            'articulo' => 'Artículo',
                                            'dataset' => 'Dataset',
                                            'codigo' => 'Código',
                                            'modelo' => 'Modelo',
                                            'prototipo' => 'Prototipo',
                                            'dashboard' => 'Dashboard',
                                            'curso' => 'Curso o capacitación',
                                        ])
                                        ->native(false)
                                        ->required(),
                                    TextInput::make('nombre')->label('Nombre')->required(),
                                    Select::make('estado')
                                        ->label('Revisión')
                                        ->options([
                                            'pendiente' => 'Pendiente',
                                            'observado' => 'Observado',
                                            'aprobado' => 'Aprobado',
                                            'entregado' => 'Entregado',
                                        ])
                                        ->default('pendiente')
                                        ->native(false),
                                ]),
                                TextInput::make('enlace')->label('Archivo o enlace')->url(),
                            ])
                            ->addActionLabel('Agregar entregable')
                            ->columnSpanFull(),
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            Select::make('propiedad_resultado')
                                ->label('Propiedad del resultado')
                                ->options([
                                    'cliente' => 'Cliente',
                                    'damian' => 'Damian Company',
                                    'compartida' => 'Compartida',
                                    'otra' => 'Otra',
                                ])
                                ->native(false),
                            Select::make('permiso_publicacion')
                                ->label('Permiso de publicación')
                                ->options([
                                    'permitido' => 'Permitido',
                                    'restringido' => 'Restringido',
                                    'requiere_aprobacion' => 'Requiere aprobación',
                                ])
                                ->native(false),
                            DatePicker::make('fecha_cierre')->label('Fecha de cierre')->native(false),
                        ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function estados(): array
    {
        return [
            'idea_registrada' => 'Idea registrada',
            'en_definicion' => 'En definición',
            'en_evaluacion' => 'En evaluación',
            'proyecto_activo' => 'Proyecto activo',
            'en_ejecucion' => 'En ejecución',
            'bloqueado' => 'Bloqueado',
            'en_analisis' => 'En análisis',
            'en_validacion' => 'En validación',
            'en_documentacion' => 'En documentación',
            'listo_entrega' => 'Listo para entrega',
            'entregado' => 'Entregado',
            'cerrado' => 'Cerrado',
        ];
    }
}
