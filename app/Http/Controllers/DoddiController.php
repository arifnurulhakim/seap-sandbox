<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;

class DoddiController extends Controller{
   public function checkdb() {
      try {
         $dbconnect = DB::connection()->getPDO();
         $dbname = DB::connection()->getDatabaseName();
         echo "Connected successfully to the database. Database name is :".$dbname;
      } catch(Exception $e) {
         echo "Error in connecting to the database";
      }
   }

   public function hello() {
      return 'Test 123';
   }
}