<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CategoriaProveedorDami3d extends Model { protected $table='categorias_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['activo'=>'boolean']; } public function proveedores(){ return $this->belongsToMany(ProveedorDami3d::class,'proveedor_categoria_dami3d','categoria_id','proveedor_id'); } }
