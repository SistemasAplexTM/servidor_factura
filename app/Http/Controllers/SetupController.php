<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Type;

class SetupController extends Controller
{

    public function get(){
      $branch_office_id = \Auth::user()->sucursal_id;
      $type = $this->getType($branch_office_id);
      return ['data' => $type];
    }

    public function getType($branch_office_id){
      return Type::where('tiendas', $branch_office_id)->first();
    }
}
