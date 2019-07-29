<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\BlockOfCash;

class GlobalCOntroller extends Controller
{

 public function validateCashRegister($branch)
 {
  $data = BlockOfCash::where([['sucursal_id',$branch], ['fecha',date('Y-m-d')]])->get();
  return response()->json(['data' => $data]);
 }

 public function saveCashRegister(Request $request)
 {
  BlockOfCash::insert([
   'sucursal_id' => $request->user['sucursal_id'],
   'user_id' => $request->user['id'],
   'fecha' => date('Y-m-d'),
   'valor_caja' => $request->value,
  ]);
  return response()->json(['code' => 200]);
 }
}
