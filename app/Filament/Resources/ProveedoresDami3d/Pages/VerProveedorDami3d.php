<?php

namespace App\Filament\Resources\ProveedoresDami3d\Pages;

use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use App\Jobs\SincronizarDocumentosProveedorDami3d;
use App\Models\ProductoProveedorDami3d;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Size;

class VerProveedorDami3d extends ViewRecord
{
    protected static string $resource = ProveedorDami3dResource::class;
    protected string $view = 'filament.resources.proveedores-dami3d.pages.ver-proveedor-dami3d';

    public function getTitle(): string { return $this->record->razon_social; }
    public function getSubheading(): ?string { return $this->record->codigo.' · Proveedor DAMI 3D'; }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')->label('Contactar por WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->color('success')->visible(fn()=>filled($this->record->whatsapp))->url(fn()=>'https://wa.me/'.preg_replace('/\D+/','',$this->record->whatsapp))->openUrlInNewTab(),
            Action::make('producto')->label('Registrar producto')->icon('heroicon-o-plus')->color('success')->schema($this->camposProducto())->action(function(array $data):void { $this->record->productos()->create($data+['precio_actualizado_en'=>now()]); $this->actividad('Producto registrado',$data['nombre']); $this->ok('Producto registrado'); }),
            Action::make('precio')->label('Actualizar precio')->icon('heroicon-o-arrow-trending-up')->color('info')->visible(fn()=> $this->record->productos()->exists())->schema([
                Select::make('producto_id')->label('Producto')->options(fn()=> $this->record->productos()->where('activo',true)->pluck('nombre','id'))->required()->searchable(),
                TextInput::make('precio')->label('Nuevo precio')->prefix('S/')->numeric()->minValue(0)->required(),
            ])->action(function(array $data):void { $p=$this->record->productos()->findOrFail($data['producto_id']); $p->update(['precio_referencial'=>$data['precio'],'precio_actualizado_en'=>now()]); $this->actividad('Precio actualizado',$p->nombre.' · S/ '.$data['precio']); $this->ok('Precio actualizado'); }),
            Action::make('evaluar')->label('Evaluar')->icon('heroicon-o-star')->schema($this->camposEvaluacion())->action(function(array $data):void { $notas=collect($data)->only(['precio','calidad','entrega','atencion','cantidades','estado_producto','pago','reclamos']); $data['promedio']=round($notas->avg(),2); $data['evaluado_por']=auth()->id(); $this->record->evaluaciones()->create($data); $this->record->updateQuietly(['calificacion'=>round($this->record->evaluaciones()->avg('promedio'),2)]); $this->actividad('Evaluación registrada','Promedio '.$data['promedio']); $this->ok('Evaluación guardada'); }),
            Action::make('incidencia')->label('Reportar incidencia')->icon('heroicon-o-exclamation-triangle')->color('warning')->schema([
                Select::make('tipo')->options(['entrega_retrasada'=>'Entrega retrasada','producto_equivocado'=>'Producto equivocado','cantidad_incompleta'=>'Cantidad incompleta','material_danado'=>'Material dañado','material_vencido'=>'Material vencido','precio_diferente'=>'Precio diferente','mala_atencion'=>'Mala atención','falta_comprobante'=>'Falta de comprobante','garantia'=>'Problema con garantía','otro'=>'Otro'])->required()->native(false),
                DatePicker::make('fecha')->default(today())->required(), Textarea::make('descripcion')->required()->rows(3),
            ])->action(function(array $data):void { $this->record->incidencias()->create($data+['reportado_por'=>auth()->id()]); $this->actividad('Incidencia registrada',$data['descripcion']); $this->ok('Incidencia registrada'); }),
            Action::make('resolver_incidencia')->label('Actualizar incidencia')->icon('heroicon-o-check-circle')->visible(fn()=> $this->record->incidencias()->whereNotIn('estado',['cerrada','solucionada'])->exists())->schema([
                Select::make('incidencia_id')->label('Incidencia')->options(fn()=> $this->record->incidencias()->whereNotIn('estado',['cerrada','solucionada'])->get()->mapWithKeys(fn($i)=>[$i->id=>str($i->tipo)->replace('_',' ')->title().' · '.$i->fecha->format('d/m/Y')]))->required(),
                Select::make('estado')->options(['pendiente'=>'Pendiente','revision'=>'En revisión','solucionada'=>'Solucionada','cerrada'=>'Cerrada','no_solucionada'=>'No solucionada'])->required()->native(false),
                Textarea::make('solucion')->label('Seguimiento o solución')->required()->rows(3),
            ])->action(function(array $data):void { $i=$this->record->incidencias()->findOrFail($data['incidencia_id']); $i->update(['estado'=>$data['estado'],'solucion'=>$data['solucion'],'fecha_cierre'=>in_array($data['estado'],['solucionada','cerrada','no_solucionada'])?today():null]); $this->actividad('Incidencia actualizada',$data['solucion']); $this->ok('Incidencia actualizada'); }),
            Action::make('documento')->label('Adjuntar documento')->icon('heroicon-o-paper-clip')->schema([
                Select::make('tipo')->options(fn():array=>array_filter(['ficha_ruc'=>'Ficha RUC','catalogo'=>'Catálogo','lista_precios'=>'Lista de precios','cotizacion'=>'Cotización','datos_bancarios'=>auth()->user()?->role==='gerencia'?'Datos bancarios':null,'garantia'=>'Garantía','certificado'=>'Certificado','otro'=>'Otro']))->required()->native(false),
                FileUpload::make('archivo')->label('Archivo')->disk('local')->directory('proveedores-dami3d/temporales')->acceptedFileTypes(['application/pdf','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','image/jpeg','image/png','image/webp'])->maxSize(15360)->required(),
            ])->action(function(array $data):void { $ruta=$data['archivo']; $this->record->documentos()->create(['tipo'=>$data['tipo'],'nombre_original'=>basename($ruta),'ruta_temporal'=>$ruta,'cargado_por'=>auth()->id()]); if(filled(config('services.google_drive.proveedores_dami3d_root_folder_id'))) SincronizarDocumentosProveedorDami3d::dispatch($this->record->id); $this->actividad('Documento adjuntado',basename($ruta)); $this->ok('Documento registrado'); }),
            Action::make('estado')->label('Cambiar estado')->icon('heroicon-o-shield-check')->visible(fn()=>auth()->user()?->role==='gerencia')->schema([
                Select::make('estado')->options(['evaluacion'=>'En evaluación','activo'=>'Activo','suspendido'=>'Suspendido','bloqueado'=>'Bloqueado','inactivo'=>'Inactivo'])->required()->native(false), Textarea::make('motivo')->label('Motivo')->required()->rows(2),
            ])->fillForm(fn()=>['estado'=>$this->record->estado])->action(function(array $data):void { $this->record->update(['estado'=>$data['estado'],'motivo_estado'=>$data['motivo']]); $this->ok('Estado actualizado'); }),
            Action::make('editar')->label('Editar proveedor')->icon('heroicon-o-pencil-square')->size(Size::Large)->url(static::getResource()::getUrl('edit',['record'=>$this->record])),
            Action::make('regresar')->label('Regresar')->icon('heroicon-o-arrow-left')->size(Size::Large)->url(static::getResource()::getUrl()),
        ];
    }

    private function camposProducto(): array
    {
        return [Grid::make(2)->schema([
            TextInput::make('nombre')->required(), Select::make('categoria_id')->label('Categoría')->options(\App\Models\CategoriaProveedorDami3d::where('activo',true)->pluck('nombre','id'))->required()->native(false),
            Select::make('marca_id')->label('Marca')->options(\App\Models\MarcaProveedorDami3d::where('activo',true)->pluck('nombre','id'))->searchable()->native(false), TextInput::make('presentacion')->placeholder('Ej. rollo 1 kg'),
            TextInput::make('unidad_medida')->label('Unidad')->placeholder('kg, unidad, litro'), TextInput::make('precio_referencial')->label('Precio')->prefix('S/')->numeric()->minValue(0)->required(),
            Select::make('moneda')->options(['PEN'=>'Soles','USD'=>'Dólares'])->default('PEN')->required(), Select::make('disponibilidad')->options(['disponible'=>'Disponible','consultar'=>'Consultar','sin_stock'=>'Sin stock'])->default('consultar')->required(),
            Toggle::make('igv_incluido')->label('IGV incluido')->default(true), TextInput::make('entrega_dias')->label('Entrega')->suffix('días')->numeric()->minValue(0),
        ])];
    }

    private function camposEvaluacion(): array
    {
        $campos=[]; foreach(['precio'=>'Precio','calidad'=>'Calidad','entrega'=>'Entrega','atencion'=>'Atención','cantidades'=>'Cantidades','estado_producto'=>'Estado del producto','pago'=>'Condiciones de pago','reclamos'=>'Respuesta a reclamos'] as $nombre=>$label) $campos[]=Select::make($nombre)->label($label)->options([1=>'1 · Deficiente',2=>'2',3=>'3 · Regular',4=>'4',5=>'5 · Excelente'])->required()->native(false);
        $campos[]=Textarea::make('comentario')->rows(2)->columnSpanFull(); return [Grid::make(2)->schema($campos)];
    }
    private function actividad(string $accion,string $detalle):void { $this->record->actividad()->create(['accion'=>$accion,'detalle'=>$detalle,'usuario_id'=>auth()->id()]); }
    private function ok(string $titulo):void { Notification::make()->success()->title($titulo)->send(); $this->redirect(static::getResource()::getUrl('view',['record'=>$this->record])); }
}
