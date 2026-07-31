<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductoProveedorDami3d extends Model {
 protected $table='productos_proveedor_dami3d'; protected $guarded=[];
 protected function casts(): array { return ['precio_referencial'=>'decimal:2','precio_actualizado_en'=>'datetime','igv_incluido'=>'boolean','activo'=>'boolean']; }
 public function proveedor(): BelongsTo { return $this->belongsTo(ProveedorDami3d::class,'proveedor_id'); }
 public function categoria(): BelongsTo { return $this->belongsTo(CategoriaProveedorDami3d::class,'categoria_id'); }
 public function marca(): BelongsTo { return $this->belongsTo(MarcaProveedorDami3d::class,'marca_id'); }
 public function historialPrecios(): HasMany { return $this->hasMany(HistorialPrecioProveedorDami3d::class,'producto_id'); }
 protected static function booted(): void { static::created(fn(self $p)=>$p->historialPrecios()->create(['precio_nuevo'=>$p->precio_referencial,'moneda'=>$p->moneda,'registrado_por'=>auth()->id()])); static::updated(function(self $p):void { if($p->wasChanged('precio_referencial')) $p->historialPrecios()->create(['precio_anterior'=>$p->getPrevious()['precio_referencial'] ?? null,'precio_nuevo'=>$p->precio_referencial,'moneda'=>$p->moneda,'registrado_por'=>auth()->id()]); }); }
}
