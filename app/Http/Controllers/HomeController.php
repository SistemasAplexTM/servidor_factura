<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Tercero;
use Rap2hpoutre\FastExcel\FastExcel;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function algo()
    {
     return User::all();
    }

    // public function test()
    // {
    //  $writer = WriterFactory::create(Type::XLSX); // for XLSX files
    //  //$writer = WriterFactory::create(Type::CSV); // for CSV files
    //  //$writer = WriterFactory::create(Type::ODS); // for ODS files
    //
    //  // $writer->openToFile($filePath); // write data to a file or to a PHP stream
    //  $writer->openToBrowser('test.xlsx'); // stream data directly to the browser
    //  $data = Tercero::select('id', 'nombre', 'direccion', 'telefono', 'email')->where('id', 100)->get();
    //  // return response()->json($data);
    //  // $writer->addRow([$collection]); // add a row at a time
    //  $writer->addRows($data); // add multiple rows at a time
    //  //
    //  $writer->close();
    // }

    public function test()
    {
     // Load users
     // return $users;
     // Export all users
     return (new FastExcel(Tercero::all()))->download('fileN.xlsx');
     // return $users;
    }
}
