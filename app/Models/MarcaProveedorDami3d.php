<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MarcaProveedorDami3d extends Model { protected $table='marcas_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['activo'=>'boolean']; } }
