<?php

namespace App\Http\Controllers\People;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\People;

class IndexController extends Controller
{
    public function save(Request $request)
    {
     DB::beginTransaction();
       try {
           $data = Tercero::create($request->all());
           $answer = array(
               "data"  => $data,
               "code"   => 200
           );
           DB::commit();
           return $answer;
       } catch (\Exception $e) {
           DB::rollback();
           $answer = array(
               "error" => $e,
               "code"  => 600,
           );
           return $answer;
       }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            Tercero::where('id', $request->id)->update($request->all());
            $answer = array(
                "datos"  => '',
                "code"   => 200
            );
            DB::commit();
            return $answer;
        } catch (\Exception $e) {
            DB::rollback();
            $answer = array(
                "error" => $e,
                "code"  => 600,
            );
            return $answer;
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = Tercero::findOrFail($request->id);
            if ($data->delete()) {
                // $this->AddToLog('Registro de tabla admin eliminada (id :'.$data->id.')');
                $answer = array(
                    "code" => 200,
                );
            }
            return $answer;
        } catch (\Exception $e) {
            $answer = array(
                "error" => $e,
                "code"  => 600,
            );
            return $answer;
        }
    }

    public function getById($id)
    {
        $data = Tercero::findOrFail($id);
        return $data;
    }

    public function search($data, $type)
    {
     // DB::connection()->enableQueryLog();
     $where = array(['nombre', 'LIKE', '%' . $data . '%']);
     $orWhere = array(['documento', 'LIKE', $data . '%']);
      switch ($type) {
        case 'cliente':
          $answer = People::where($where)->where($type, 1)->orWhere($orWhere)->get();
        break;
        case 'vendedor':
        $answer = DB::table('pivot_tienda_vendedor AS a')
        ->join('terceros AS b', 'b.id', 'a.id_vendedor')
        ->select('*')
        ->whereRaw('(nombre LIKE "%'  . $data .  '%" or documento LIKE "' . $data . '%")')
        ->where('a.id_tienda', Auth::user()->sucursal_id)
        ->get();
        // $answer = People::join('pivot_tienda_vendedor AS b', 'b.id_vendedor', 'terceros.id')
        // ->where($type, 1)->where($where)->where([['b.id_tienda', Auth::user()->sucursal_id]])
        // ->orWhere($orWhere)->get();
        break;
        default:

          break;
      }
      // return DB::getQueryLog();
      return array( 'data' => $answer );
    }

}
