<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Product;

class IndexController extends Controller
{
 public function getByCode(Request $request)
 {
   $bodega = $request->branch_office_id;
   $code = $request->code;
   $wholesale = $request->wholesale;
   $data = Product::select(
     DB::raw("producto.id AS product_id,
     producto.descripcion AS producto,
     precio_venta,
     precio_pormayor,
     precio_con_iva,
     categoria_id,
     costo,
     valor,
     1 AS cantidad,
     0 AS descuento,
     ((1 * precio_venta) + ROUND(b.valor * precio_venta / 100)) AS monto_total,
     ROUND(b.valor * precio_venta / 100) AS iva,
     ROUND(b.valor,0) AS porcentaje_iva"
         )
     )->join('iva AS b', 'iva_id', 'b.id')->where('codigo', $code)->first();

   if ($data) {
     $saldo = DB::select(DB::raw("CALL getSaldoProducto(:bodega, :id)"),[
         ':bodega' => $bodega,
         ':id' => $data->product_id,
     ]);
     if ($saldo[0]->saldo != '') {
       if ($saldo[0]->saldo > 0) {
         $data->saldo = $saldo[0]->saldo;
         $promo = $this->getPromos('producto_id', $data->product_id);
         $data->descuento_venta= 0;
         $data->monto_total = $data->precio_venta + round(($data->valor * $data->precio_venta / 100));
         $data->iva = round(($data->valor * $data->precio_venta / 100));
         $data->descuento_pormayor = 0;
         $data->iva_pormayor = round(($data->valor * $data->precio_pormayor / 100));
         $data->monto_total_pormayor = $data->precio_pormayor + round(($data->valor * $data->precio_pormayor / 100));
         if (!$promo) {
           $promo = $this->getPromos('categoria_id', $data->categoria_id);
         }
         if ($promo) {
           $tiendas = json_decode($promo->tiendas);
           //DESTO POR VALOR VENTA
           if($promo->precio_nuevo != 0){
             $result = $this->calculateFile($data->precio_venta, $promo->precio_nuevo, $data->valor, $promo->tipo_desto_v, $tiendas, $bodega);
             $data->descuento_venta= $result['descuento'];
             $data->iva = $result['iva'];
             $data->monto_total = $result['monto_total'];
           }
           //DESTO POR VALOR AL POR MAYOR
           if($promo->precio_por_mayor_nuevo != 0){
             $result = $this->calculateFile($data->precio_pormayor, $promo->precio_por_mayor_nuevo, $data->valor, $promo->tipo_desto_p, $tiendas, $bodega);
             $data->descuento_pormayor = $result['descuento'];
             $data->iva_pormayor = $result['iva'];
             $data->monto_total_pormayor = $result['monto_total'];
           }
         }

         // PROMOCIONES
         $answer = array(
           'code' => 200,
           'data' => $data,
           'promo' => $promo
         );
       }else{
         $answer = array(
           'code' => 600,
           'msg' => 'El producto no tiene saldo suficiente. Saldo: ' . $saldo[0]->saldo,
         );
       }
     }else{
       $answer = array(
         'code' => 600,
         'msg' => 'El producto no existe en bodega',
       );
     }
   }else{
     $answer = array(
       'code' => 404,
       'msg' => 'El producto no existe en la base de datos'
     );
   }
   return $answer;
 }

 public function getPromos($type, $id)
 {
   $promo = DB::table('promociones_detalle AS a')
   ->join('promociones AS b', 'a.promociones_id', 'b.id')
   ->select(
     'a.precio_nuevo',
     'a.tipo_desto_v',
     'a.precio_por_mayor_nuevo',
     'a.tipo_desto_p',
     'b.tiendas'
     )
   ->where([
     ['a.deleted_at', NULL],
     ['b.deleted_at', NULL],
     ['a.activado', 1],
     ['b.activado', 1],
     ['b.fecha_ini', '<=', date('Y-m-d')],
     ['b.fecha_fin', '>=', date('Y-m-d')],
     ['a.'.$type, $id]
   ])
   ->first();
   return ($promo) ? $promo : false;
 }

 public function calculateFile($precio_venta, $precio_nuevo, $iva, $tipo_desto, $tiendas, $bodega)
 {
   foreach ($tiendas as $tienda) {
     if($bodega == $tienda){
       if($tipo_desto == 0){// si es desto por valor
         $descuento = $precio_venta - ($precio_nuevo / (1 + ($iva / 100)));
       }else{
         $descuento = $precio_venta * ($precio_nuevo / 100);
       }
       $valor_iva = ($precio_venta - $descuento) * ($iva / 100);
       $monto_total = $precio_venta - $descuento + $valor_iva;
     }
   }
   return ['precio_venta' => $precio_venta, 'descuento' => round($descuento, 0), 'iva' => round($valor_iva, 0), 'monto_total' => $monto_total];
 }
}
