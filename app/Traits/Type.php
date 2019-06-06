<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Type as TypeModel;

trait Type {

  public function getType($id)
  {
   try {
    return TypeModel::where('tiendas', $id)->first();
   } catch (\Exception $e) {
    return $e;
   }

  }

}
