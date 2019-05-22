<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Type;
use App\Document;


class IndexController extends Controller
{
    public function getTypes($rol = 1, $branch = 10)
    {
     return Type::select('id', 'descripcion', 'icono')
     ->whereRaw('FIND_IN_SET(?,credenciales)', [$rol])
     ->whereRaw('FIND_IN_SET(?,tiendas)', [$branch])
     ->orWhere('tiendas',  null)
     ->where('deleted_at', null)
     ->get();
    }

    public function getDocuments($id)
    {
     return Document::with('type', 'client', 'branch')->where('tipo_id', $id)->get()->toJson();
    }
}
