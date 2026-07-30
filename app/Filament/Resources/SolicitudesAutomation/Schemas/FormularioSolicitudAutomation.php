<?php

namespace App\Filament\Resources\SolicitudesAutomation\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormularioSolicitudAutomation
{
    public static function configurar(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nueva solicitud')
                ->description('Registra la necesidad del cliente. La ingeniería se completa después.')
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('cliente')->label('Cliente')->required()->minLength(3)->maxLength(255),
                        TextInput::make('titulo')->label('Nombre del pedido o proyecto')->required()->minLength(5)->maxLength(255),
                        TextInput::make('contacto_nombre')->label('Contacto principal')->maxLength(255),
                        TextInput::make('contacto_medio')->label('WhatsApp o correo')->maxLength(255),
                        Select::make('tipo_servicio')
                            ->label('Tipo de servicio')
                            ->options(self::tiposServicio())
                            ->searchable()
                            ->native(false)
                            ->required(),
                        TextInput::make('ubicacion')->label('Ubicación o sede')->maxLength(255),
                    ]),
                    Textarea::make('necesidad')
                        ->label('Necesidad o problema')
                        ->placeholder('Describe en pocas líneas qué necesita resolver el cliente.')
                        ->required()
                        ->rows(4)
                        ->minLength(20)
                        ->maxLength(2500)
                        ->columnSpanFull(),
                    TextInput::make('resultado_esperado')
                        ->label('Resultado esperado')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Toggle::make('requiere_visita')->label('Requiere visita técnica')->default(false),
                ]),
            Section::make('Condiciones iniciales')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])->schema([
                        DatePicker::make('fecha_requerida')->label('Fecha requerida')->minDate(today())->native(false),
                        TextInput::make('presupuesto_referencial')->label('Presupuesto referencial')->prefix('S/')->numeric()->minValue(0),
                        Select::make('prioridad')
                            ->label('Prioridad')
                            ->options(['normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'])
                            ->default('normal')
                            ->native(false)
                            ->required(),
                    ]),
                    FileUpload::make('archivos_temporales')
                        ->label('Fotos, planos o documentos')
                        ->multiple()
                        ->maxFiles(8)
                        ->maxSize(102400)
                        ->disk('local')
                        ->directory('automation/temporales')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'image/*', 'video/*', 'application/pdf', 'text/plain',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/zip', 'application/x-zip-compressed',
                            'application/dxf', 'application/octet-stream',
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function tiposServicio(): array
    {
        return [
            'automatizacion' => 'Automatización industrial',
            'maquina' => 'Diseño y fabricación de máquina',
            'plc_hmi' => 'PLC / HMI',
            'tablero' => 'Tablero eléctrico o de control',
            'web' => 'Sistema web',
            'app' => 'Aplicación',
            'iot' => 'IoT',
            'inteligencia_artificial' => 'Inteligencia artificial',
            'otro' => 'Otro',
        ];
    }
}
