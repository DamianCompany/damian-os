<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IncidenciaProveedorDami3d extends Model { protected $table='incidencias_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['fecha'=>'date','fecha_cierre'=>'date']; } }
