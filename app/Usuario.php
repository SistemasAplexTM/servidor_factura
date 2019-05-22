<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';
    protected $fillable = [
     'user_name',
     'actived',
     'num_doc',
     'sucursal_id',
     'credencial_id'
    ];

    public function user()
    {
       return $this->belongsTo('App\User');
    }
}
