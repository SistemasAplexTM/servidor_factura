<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Type;
use App\Document;
use App\DocumentDetail;
use App\Product;


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

    public function getDocuments(Request $request, $id)
    {
     $count = Document::with('type', 'client', 'branch')->where('tipo_id', $id)->count();
     $data = Document::with('type', 'client', 'branch')->where('tipo_id', $id)
     ->skip($request->page * $request->perPage)->take($request->perPage)
     ->orderBy($request->sort['field'], $request->sort['type'])->get();
     return response()->json(['totalRecords' => $count, 'rows' => $data]);
    }

    public function TestDetail()
    {
     DB::connection()->enableQueryLog();
     $data = DocumentDetail::select(DB::raw('Sum(cant_final) AS cantidad, producto_id, bodega_id'))->with('branch', 'product')
     ->groupBy('producto_id', 'bodega_id')->havingRaw('cantidad > 0')->get();
     // return DB::getQueryLog();
     return $data;
    }
}
