<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormularioSolicitudInvestiga
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nueva idea o solicitud')
                ->description('Registra el problema y el resultado esperado. Los detalles técnicos se completarán después.')
                ->icon('heroicon-o-light-bulb')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('solicitante')
                            ->label('Solicitante')
                            ->placeholder('Persona, empresa o institución')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255),
                        TextInput::make('titulo')
                            ->label('Título breve')
                            ->placeholder('Nombre provisional de la iniciativa')
                            ->required()
                            ->minLength(5)
                            ->maxLength(255),
                        Select::make('sector')
                            ->label('Área o sector')
                            ->options([
                                'educacion' => 'Educación',
                                'agricultura' => 'Agricultura',
                                'industria' => 'Industria',
                                'transporte' => 'Transporte',
                                'salud' => 'Salud',
                                'construccion' => 'Construcción',
                                'ambiente' => 'Ambiente',
                                'otro' => 'Otro',
                            ])
                            ->searchable()
                            ->native(false)
                            ->required(),
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
                            ->required(),
                    ]),
                    Textarea::make('problema_necesidad')
                        ->label('Problema o necesidad')
                        ->placeholder('Descríbelo de forma concreta en 2 a 5 líneas.')
                        ->required()
                        ->rows(4)
                        ->minLength(20)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    TextInput::make('resultado_esperado')
                        ->label('Resultado esperado')
                        ->placeholder('Ejemplo: informe, prototipo, modelo, dashboard o curso')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Section::make('Condiciones iniciales')
                ->description('Estos datos son opcionales y pueden completarse durante la revisión.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        DatePicker::make('fecha_requerida')
                            ->label('Fecha requerida')
                            ->minDate(today())
                            ->native(false),
                        TextInput::make('presupuesto_referencial')
                            ->label('Presupuesto referencial')
                            ->prefix('S/')
                            ->numeric()
                            ->minValue(0),
                        Select::make('confidencialidad')
                            ->label('Confidencialidad')
                            ->options([
                                'normal' => 'Normal',
                                'restringido' => 'Restringido',
                                'confidencial' => 'Confidencial',
                            ])
                            ->default('normal')
                            ->native(false)
                            ->required(),
                    ]),
                    FileUpload::make('archivos_temporales')
                        ->label('Archivos de referencia')
                        ->helperText('Opcional. Hasta 5 archivos entre imágenes, PDF, documentos, hojas de cálculo, CSV o ZIP.')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(51200)
                        ->disk('local')
                        ->directory('investiga-lab/temporales')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'image/*',
                            'application/pdf',
                            'text/plain',
                            'text/csv',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/zip',
                            'application/x-zip-compressed',
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
