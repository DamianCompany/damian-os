<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HistorialPrecioProveedorDami3d extends Model { public $timestamps=false; protected $table='historial_precios_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['created_at'=>'datetime']; } }
