<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActividadProveedorDami3d extends Model { public $timestamps=false; protected $table='actividad_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['created_at'=>'datetime']; } }
