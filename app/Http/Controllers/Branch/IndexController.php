<?php

namespace App\Http\Controllers\Branch;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Branch;

class IndexController extends Controller
{
 public function get()
 {
   $data = Branch::select(
     'id',
     'razon_social AS name',
     'nit',
     'ciudad',
     'pais'
     )
   ->where([
    ['id', '<>', 0],
    ['id', '<>', 1]
   ])
   ->orderBy('name', 'ASC')
   ->get();
   return array('data' => $data);
 }

}
