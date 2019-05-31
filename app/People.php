<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class People extends Model
{
    protected $table = 'terceros';
    protected $fillable = [
     'id',
     'nombre',
     'direccion',
     'telefono',
     'ciudad',
     'email',
     'nacimiento',
     'created_at',
     'updated_at',
     'deleted_at',
     'documento',
     'usado',
     'vendedor',
     'proveedor',
     'datos_proveedor',
     'cliente'
    ];

    // public function branch()
    // {
    //  return $this->belongsToMany('\App\Branch','pivot_tienda_vendedor', 'id_vendedor', 'id_tienda')
    //         ->withPivot('id_tienda','id_vendedor')->where('id_tienda', Auth::user()->sucursal_id);
    // }
}
