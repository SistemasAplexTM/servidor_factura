<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
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

}
