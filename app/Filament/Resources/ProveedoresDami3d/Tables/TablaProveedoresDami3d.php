<?php

namespace App\Filament\Resources\ProveedoresDami3d\Tables;

use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use App\Models\CategoriaProveedorDami3d;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TablaProveedoresDami3d
{
    public static function configurar(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'damian-supplier-table'])
            ->columns([
            TextColumn::make('codigo')->label('Código')->searchable()->sortable()->weight('semibold'),
            TextColumn::make('razon_social')->label('Proveedor')->description(fn($record)=>$record->nombre_comercial ?: $record->numero_documento)->searchable(['razon_social','nombre_comercial','numero_documento','whatsapp'])->wrap(),
            TextColumn::make('categorias.nombre')->label('Materiales')->badge()->limitList(2)->expandableLimitedList(),
            TextColumn::make('whatsapp')->label('Contacto')->icon('heroicon-o-chat-bubble-left-right')->placeholder('Sin WhatsApp'),
            TextColumn::make('entrega_promedio_dias')->label('Entrega')->formatStateUsing(fn($s)=>$s!==null?$s.' días':'Por consultar'),
            TextColumn::make('calificacion')->label('Calificación')->formatStateUsing(fn($s)=>number_format((float)$s,1).' / 5')->icon('heroicon-o-star'),
            TextColumn::make('estado')->label('Estado')->badge()->formatStateUsing(fn($s)=>self::estados()[$s]??$s)->color(fn($s)=>match($s){'activo'=>'success','evaluacion'=>'warning','suspendido'=>'orange','bloqueado'=>'danger',default=>'gray'}),
        ])->filters([
            SelectFilter::make('estado')->options(self::estados()),
            SelectFilter::make('categorias')->relationship('categorias','nombre')->multiple()->preload(),
            SelectFilter::make('principal')->label('Proveedor principal')->options(['1'=>'Sí','0'=>'No']),
        ])->defaultSort('created_at','desc')->recordUrl(fn($record)=>ProveedorDami3dResource::getUrl('view',['record'=>$record]))
          ->recordActions([ViewAction::make()->label('Ver ficha')])
          ->emptyStateIcon('heroicon-o-building-storefront')->emptyStateHeading('Aún no hay proveedores')->emptyStateDescription('Registra quién abastece materiales y repuestos de DAMI 3D.');
    }

    public static function estados(): array { return ['evaluacion'=>'En evaluación','activo'=>'Activo','suspendido'=>'Suspendido','bloqueado'=>'Bloqueado','inactivo'=>'Inactivo']; }
}
