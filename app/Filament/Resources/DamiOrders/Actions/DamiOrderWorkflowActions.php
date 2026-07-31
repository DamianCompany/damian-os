<?php

namespace App\Filament\Resources\DamiOrders\Actions;

use App\Models\DamiOrder;
use App\Models\ProductoProveedorDami3d;
use App\Models\ProveedorDami3d;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class DamiOrderWorkflowActions
{
    public static function start(): Action
    {
        return Action::make('startProduction')
            ->label('Iniciar producción')
            ->icon('heroicon-o-play')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('¿Iniciar la producción?')
            ->modalDescription('El pedido cambiará de Pendiente a En proceso.')
            ->modalSubmitActionLabel('Sí, iniciar')
            ->visible(fn (DamiOrder $record): bool => self::isSupervisor() && $record->status === 'pending')
            ->action(function (DamiOrder $record): void {
                $record->update(['status' => 'in_progress']);

                Notification::make()
                    ->success()
                    ->title('Producción iniciada')
                    ->body("El pedido {$record->order_number} está en proceso.")
                    ->send();
            });
    }

    public static function updateProduction(): Action
    {
        return Action::make('updateProduction')
            ->label('Completar información')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('gray')
            ->modalHeading('Completar información de producción')
            ->modalDescription('Añade únicamente los datos que quedaron pendientes.')
            ->modalSubmitActionLabel('Guardar información')
            ->visible(fn (DamiOrder $record): bool => self::isSupervisor() && $record->status !== 'completed')
            ->fillForm(fn (DamiOrder $record): array => [
                'postprocess_hours' => $record->postprocess_hours,
                'proveedor_dami3d_id' => $record->proveedor_dami3d_id,
                'producto_proveedor_dami3d_id' => $record->producto_proveedor_dami3d_id,
                'reference_images' => $record->referenceFiles()->orderBy('id')->pluck('path')->all(),
                'received_file' => $record->files()->where('type', 'received')->value('path'),
            ])
            ->schema([
                Grid::make(2)->schema([
                    Select::make('proveedor_dami3d_id')
                        ->label('Proveedor del material')
                        ->helperText('Opcional. Selecciona primero el proveedor.')
                        ->options(fn (): array => ProveedorDami3d::query()
                            ->whereIn('estado', ['evaluacion', 'activo'])
                            ->orderBy('razon_social')
                            ->pluck('razon_social', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('producto_proveedor_dami3d_id', null)),
                    Select::make('producto_proveedor_dami3d_id')
                        ->label('Producto o filamento')
                        ->helperText(fn (Get $get): string => filled($get('proveedor_dami3d_id'))
                            ? 'Su marca y categoría se vincularán automáticamente.'
                            : 'Selecciona primero un proveedor.')
                        ->options(function (Get $get): array {
                            if (blank($proveedorId = $get('proveedor_dami3d_id'))) {
                                return [];
                            }

                            return ProductoProveedorDami3d::query()
                                ->with('marca')
                                ->where('proveedor_id', $proveedorId)
                                ->where('activo', true)
                                ->orderBy('nombre')
                                ->get()
                                ->mapWithKeys(fn (ProductoProveedorDami3d $producto): array => [
                                    $producto->id => $producto->nombre.($producto->marca ? ' · '.$producto->marca->nombre : ''),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->native(false)
                        ->disabled(fn (Get $get): bool => blank($get('proveedor_dami3d_id'))),
                ]),
                TextInput::make('postprocess_hours')
                    ->label('Tiempo de postproceso')
                    ->helperText('Opcional')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->suffix('h'),
                FileUpload::make('reference_images')
                    ->label('Imágenes referenciales')
                    ->helperText('Hasta 3 imágenes.')
                    ->image()
                    ->multiple()
                    ->maxFiles(3)
                    ->directory('dami-3d/references')
                    ->reorderable(),
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
            ])
            ->action(function (DamiOrder $record, array $data): void {
                $record->update([
                    'postprocess_hours' => filled($data['postprocess_hours'] ?? null)
                        ? $data['postprocess_hours']
                        : null,
                    'proveedor_dami3d_id' => $data['proveedor_dami3d_id'] ?? null,
                    'producto_proveedor_dami3d_id' => $data['producto_proveedor_dami3d_id'] ?? null,
                ]);

                $record->files()->delete();

                foreach (array_values(array_filter($data['reference_images'] ?? [])) as $path) {
                    $record->files()->create(['type' => 'reference', 'path' => $path]);
                }

                if (filled($data['received_file'] ?? null)) {
                    $record->files()->create(['type' => 'received', 'path' => $data['received_file']]);
                }

                Notification::make()
                    ->success()
                    ->title('Información actualizada')
                    ->body('Los datos de producción fueron guardados.')
                    ->send();
            });
    }

    public static function complete(): Action
    {
        return Action::make('completeProduction')
            ->label('Marcar completado')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('¿Completar este pedido?')
            ->modalDescription('Verifica los archivos y tiempos antes de confirmar.')
            ->modalSubmitActionLabel('Sí, completar')
            ->visible(fn (DamiOrder $record): bool => self::isSupervisor() && $record->status === 'in_progress')
            ->action(function (DamiOrder $record): void {
                $record->update(['status' => 'completed']);

                Notification::make()
                    ->success()
                    ->title('Pedido completado')
                    ->body("El pedido {$record->order_number} fue completado.")
                    ->send();
            });
    }

    private static function isSupervisor(): bool
    {
        return auth()->user()?->role === 'dami_3d';
    }
}
