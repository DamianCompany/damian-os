<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EvaluacionProveedorDami3d extends Model { protected $table='evaluaciones_proveedor_dami3d'; protected $guarded=[]; protected function casts(): array { return ['promedio'=>'decimal:2']; } }
