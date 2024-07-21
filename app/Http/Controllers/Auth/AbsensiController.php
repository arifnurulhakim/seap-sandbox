<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Afk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class AbsensiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        // $today = date('Y-m-d');
        // $query = DB::table('users')
        //     ->leftJoin(DB::raw("(select * from pis_absensis where start_day >= '$today' and (end_day >= pis_absensis.start_day or end_day is null))"), function($join) {
        //         $join->on('users.id', '=', 'absensis.user_id');
        //     })
        //     ->select('users.name', 'users.id as user_id', 'absensis.start_day', 'absensis.end_day', 'absensis.status', 'absensis.id as absen_id', 'absensis.created_at')
        //     ->orderBy('users.name', 'ASC')
        //     ->get();
        $today = date('Y-m-d');
        // $today = date('Y-m-d');
        $absensi = DB::select("
            SELECT users.name, absensis.start_day, absensis.end_day, absensis.status, absensis.created_at as created_at, absensis.id as absen_id, users.id as user_id 
            FROM pis_users AS users
            LEFT JOIN (
                SELECT * 
                FROM pis_absensis 
                WHERE start_day >= '$today' 
                AND (end_day >= start_day OR end_day IS NULL)
            ) AS absensis 
            ON users.id = absensis.user_id
            ORDER BY users.name ASC
        ");
        
        


            // dd($absensi);
        $timezone = 'Asia/Jakarta'; // change to your desired timezone
        $date = now($timezone);   
        $data = [];
    
        foreach ($absensi as $row) {
            
            if($row->start_day){
                $end_day = "";
                if($row->end_day){
                    $end_day = $row->end_day;
                }else{
                    $end_day = $date->format('Y-m-d H:i:s');
                }
                $total_afk="";
                $total_jam_kerja="";
                $start_afk = Afk::select('start_afk','end_afk')->where('start_afk', '>', $row->start_day)->first();
                if($start_afk){
                    $total_afk = Afk::select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(end_afk, start_afk))) AS total_durasi_afk'))
                    ->where('user_id', $row->user_id)
                    ->where('start_afk', '>=', $row->start_day)
                    ->first();
                    $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);

                    $total_waktu_afk = strtotime($total_afk);
    
                    $total_afk = gmdate('H:i:s', $total_afk->total_durasi_afk);
            
                $total_jam_kerja = $total_waktu_kerja - $total_waktu_afk;
                $total_jam_kerja = max($total_jam_kerja, 0);
                $total_jam_kerja =gmdate('H:i:s', $total_jam_kerja);    
                }
                else{
                    $total_afk = max($total_afk, 0);
                    $total_afk =gmdate('H:i:s', $total_afk);
                    $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);
                    $total_jam_kerja = max($total_waktu_kerja, 0);
                    $total_jam_kerja =gmdate('H:i:s', $total_jam_kerja);
                }
            }else{
                $end_day = null;
                $total_afk = null;
                $total_jam_kerja =null;
            }
            // retrieve the collection of Afk objects
            

// do something with the $afk_data array


             // pastikan tidak negatif
            
             $data[] = [
                'absen_id' => $row->absen_id,
                'user_id' => $row->user_id,
                'name' => $row->name,
                'start_day' => $row->start_day,
                'end_day' => $end_day,
                'total_afk' => $total_afk,
                'total_jam_kerja' => $total_jam_kerja,
                'created_at' => $row->created_at,
                'status' => $row->status,
                
                
             ];
        }
        if(empty($data)){
            return response()->json([
                'status' => 'failed',
                'data' => $data,
            ]);
        }else{
            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        }
    }
    
    

    public function getByUser(Request $request, $user_id)
    {
       
        $tanggalawal = $request->input('tanggalawal');
        $tanggalakhir = $request->input('tanggalakhir'); // add 1 day to include data on the end date
        $absensi = DB::table('users')
        ->select('users.name','users.id as user_id','absensis.id as absen_id', 'absensis.start_day','absensis.end_day','absensis.status','absensis.created_at')
            ->leftJoin('absensis', function ($join) use ($tanggalawal, $tanggalakhir) {
                $join->on('users.id', '=', 'absensis.user_id');
                if ($tanggalawal && $tanggalakhir) {
                    $join->where('absensis.start_day', '>=', $tanggalawal)
                    ->where('absensis.start_day', '<=', date('Y-m-d', strtotime($tanggalakhir. ' + 1 day')));
                }
            })
            ->where('absensis.user_id', $user_id)
            ->orderBy('users.name','asc')
            ->get();
        
            // dd($absensi);
            if ($tanggalawal && $tanggalakhir && !$absensi->count()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data absensi tidak ditemukan untuk periode yang diminta'
                ], 404);
            }

        
 
    $timezone = 'Asia/Jakarta'; // change to your desired timezone
    $date = now($timezone);   
    $data = [];
   

    foreach ($absensi as $row) {
        
        if($row->start_day){
            $end_day = "";
            if($row->end_day){
                $end_day = $row->end_day;
            }else{
                $end_day = $date->format('Y-m-d H:i:s');
            }
            $total_afk="";
            $total_jam_kerja="";
            $start_afk = Afk::select('start_afk','end_afk')->where('start_afk', '>', $row->start_day)->where('user_id',$row->user_id)->first();
            if($start_afk){
                $total_afk = Afk::select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(end_afk, start_afk))) AS total_durasi_afk'))
                ->where('user_id', $row->user_id)
                ->where('start_afk', '>=', $row->start_day)
                ->where('end_afk', '<=', $end_day)
                ->first();
                $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);
                $total_waktu_afk = $total_afk ? $total_afk->total_durasi_afk : 0;

                $total_afk = gmdate('H:i:s', $total_afk->total_durasi_afk);
        
            $total_jam_kerja = $total_waktu_kerja - $total_waktu_afk;
            $total_jam_kerja = max($total_jam_kerja, 0);

            $total_jam_kerja =gmdate('H:i:s', $total_jam_kerja);    
            }
            else{
                $total_afk = max($total_afk, 0);
                $total_afk =gmdate('H:i:s', $total_afk);
                $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);
                $total_jam_kerja = max($total_waktu_kerja, 0);
                $total_jam_kerja =gmdate('H:i:s', $total_jam_kerja);
            }

           
        }else{
            $end_day = null;
            $total_afk = null;
            $total_jam_kerja =null;
        }
         // pastikan tidak negatif

         $isStartday = false;
         $isAfk =false;
         if($row->status = 'Standby'){
            $isStartday = true;
            $isAfk =false;
         }
         else if($row->status = 'Afk'){
            $isAfk = true;
            $isStartday = true;
         }
         else if ($row->status = 'Selesai'){
            $isStartday = false;
            $isAfk = false;
         }

         $afk_session = Afk::select('start_afk','end_afk', DB::raw('TIME_TO_SEC(TIMEDIFF(end_afk, start_afk)) AS total_durasi_afk_session'))
         ->where('start_afk', '>=', $row->start_day)
         ->where('end_afk', '<=', $end_day)
         ->where('user_id', $row->user_id)
         ->groupBy('start_afk','end_afk')
         ->get();


            // initialize an empty array to store the data
            $afk_data = [];

            foreach ($afk_session as $afk) {

                $total_afk_session = gmdate('H:i:s', $afk->total_durasi_afk_session);
                $start_afk_session =date('Y-m-d H:i:s', strtotime($afk->start_afk));
                $end_afk_session =date('Y-m-d H:i:s', strtotime($afk->end_afk));
               
                // add the data to the array
                $afk_data[] = [
                    'start_afk' => $start_afk_session,
                    'end_afk' => $end_afk_session,
                    'total_afk' => $total_afk_session,
                ];
            }
        
         $data[] = [
            'absen_id' => $row->absen_id,
            'user_id' => $row->user_id,
            'name' => $row->name,
            'start_day' => $row->start_day,
            'end_day' => $end_day,
            'total_afk' => $total_afk,
            'total_jam_kerja' => $total_jam_kerja,
            'created_at' => $row->created_at,
            'status' => $row->status,
            'isStartday' =>  $isStartday,
            'isAfk' => $isAfk,
            'afk_session' =>  $afk_data
        ];
    }
    
    if(empty($data)){
        return response()->json([
            'status' => 'error',
            'message' => 'data tidak di temukan',
            'error_code' => 'DATA_NOT_FOUND'
        ]);
    }else{
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
    }
    
    public function getSelfAbsensi(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
            }
        $getiduser = $user->id;
        $tanggalawal = $request->input('tanggalawal');
        $tanggalakhir = $request->input('tanggalakhir'); // add 1 day to include data on the end date
        $absensi = DB::table('users')
        ->select('users.name','users.id as user_id','absensis.id as absen_id', 'absensis.start_day','absensis.end_day','absensis.status','absensis.created_at')
            ->leftJoin('absensis', function ($join) use ($tanggalawal, $tanggalakhir) {
                $join->on('users.id', '=', 'absensis.user_id');
                if ($tanggalawal && $tanggalakhir) {
                    $join->where('absensis.start_day', '>=', $tanggalawal)
                    ->where('absensis.start_day', '<=', date('Y-m-d', strtotime($tanggalakhir. ' + 1 day')));
                }
            })
            ->where('absensis.user_id', $getiduser)
            ->get();
            if ($tanggalawal && $tanggalakhir && !$absensi->count()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data absensi tidak ditemukan untuk periode yang diminta'
                ], 404);
            }
    $timezone = 'Asia/Jakarta'; // change to your desired timezone
    $date = now($timezone);   
    $data = [];
    $data_today = [];
    date_default_timezone_set('Asia/Jakarta');
    $today = date('Y-m-d');
    $absensi_today = Absensi::where('absensis.user_id', $getiduser)->where('start_day', '>=',$today)->first();
    
    foreach ($absensi as $row) {
        
        if($row->start_day){
            $end_day = "";
            if($row->end_day){
                $end_day = $row->end_day;
            }else{
                $end_day = $date->format('Y-m-d H:i:s');
            }
            $total_afk="";
            $total_jam_kerja="";
            $start_afk = Afk::select('start_afk','end_afk')->where('start_afk', '>', $row->start_day)->where('user_id',$row->user_id)->first();
            if($start_afk){
                $end_afk = $date->format('Y-m-d H:i:s');
                $total_afk = Afk::select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(COALESCE(end_afk, "' . $end_afk . '"), start_afk))) AS total_durasi_afk'))
                    ->where('user_id', $row->user_id)
                    ->where('start_afk', '>=', $row->start_day)
                    ->where('end_afk', '<=', $end_day)
                    ->first();
                    if($total_afk){
                        $total_afk = gmdate('H:i:s', $total_afk->total_durasi_afk);
                    }
                    else{
                        $total_afk = max($total_afk, 0);
                        $total_afk =gmdate('H:i:s', $total_afk);
                    }
                $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);
                $total_waktu_afk = $total_afk ? $total_afk->total_durasi_afk : 0;

                
        
            $total_jam_kerja = $total_waktu_kerja - $total_waktu_afk;
            $total_jam_kerja = max($total_jam_kerja, 0);
            $jam = floor($total_jam_kerja / 3600);
            $menit = floor(($total_jam_kerja % 3600) / 60);
            $detik = $total_jam_kerja % 60;
            $total_jam_kerja = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
            // $total_jam_kerja =gmdate('H:i:s', $total_jam_kerja);    
            }
            else{
                // $total_afk = max($total_afk, 0);
                // $total_afk =gmdate('H:i:s', $total_afk);
                $total_afk = '00:00:00';
                $total_waktu_kerja = strtotime($end_day) - strtotime($row->start_day);
                // dd($total_waktu_kerja);
                
                $total_jam_kerja = max($total_waktu_kerja, 0);
                $jam = floor($total_jam_kerja / 3600);
                $menit = floor(($total_jam_kerja % 3600) / 60);
                $detik = $total_jam_kerja % 60;

                $total_jam_kerja = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
                // $total_jam_kerja =gmdate('Y-m-d H:i:s', $total_jam_kerja);
            }
            
           
        }else{
            $end_day = null;
            $total_afk = null;
            $total_jam_kerja =null;
        }
     
        $start_day_todayget = $absensi_today->start_day;
        $start_day_today =date('Y-m-d H:i:s', strtotime($start_day_todayget));
     
        if($absensi_today->start_day){
            $end_day_today = "";
            if($absensi_today->end_day_today){
                $end_day_today = $absensi_today->end_day;
            }else{
                $end_day_today = $date->format('Y-m-d H:i:s');
            }
            $total_afk_today="";
            $total_jam_kerja_today="";
            $start_afk_today = Afk::select('start_afk','end_afk')->where('start_afk', '>', $absensi_today->start_day)->where('user_id',$absensi_today->user_id)->first();
            if($start_afk_today){
                $end_afk = $date->format('Y-m-d H:i:s');
                $total_afk_today = Afk::select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(COALESCE(end_afk, "' . $end_afk . '"), start_afk))) AS total_durasi_afk'))
                    ->where('user_id', $absensi_today->user_id)
                    ->where('start_afk', '>=', $absensi_today->start_day)
                    ->where('end_afk', '<=', $end_day_today)
                    ->first();
                $total_waktu_kerja_today = strtotime($end_day_today) - strtotime($absensi_today->start_day);
                $total_waktu_afk = $total_afk_today ? $total_afk_today->total_durasi_afk : 0;

                $total_afk_today = gmdate('H:i:s', $total_afk_today->total_durasi_afk);
        
            $total_jam_kerja_today = $total_waktu_kerja_today - $total_waktu_afk;
            $total_jam_kerja_today = max($total_jam_kerja_today, 0);
            $jam = floor($total_jam_kerja_today / 3600);
            $menit = floor(($total_jam_kerja_today % 3600) / 60);
            $detik = $total_jam_kerja_today % 60;
            $total_jam_kerja_today = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
            // $total_jam_kerja_today =gmdate('H:i:s', $total_jam_kerja_today);    
            }
            else{
                $total_afk_today = max($total_afk_today, 0);
                $total_afk_today =gmdate('H:i:s', $total_afk_today);
                $total_waktu_kerja_today = strtotime($end_day_today) - strtotime($absensi_today->start_day);
                // dd($total_waktu_kerja_today);
                
                $total_jam_kerja_today = max($total_waktu_kerja_today, 0);
                $jam_today = floor($total_jam_kerja_today / 3600);
                $menit_today = floor(($total_jam_kerja_today % 3600) / 60);
                $detik_today = $total_jam_kerja_today % 60;

                $total_jam_kerja_today = sprintf('%02d:%02d:%02d', $jam_today, $menit_today, $detik_today);
                // $total_jam_kerja_today =gmdate('Y-m-d H:i:s', $total_jam_kerja_today);
            }
            
           
        }else{
            $end_day_today = null;
            $total_afk_today = null;
            $total_jam_kerja_today =null;
        }
             
        
        
         $isStartday = false;
         $isAfk =false;
         if($absensi_today->status == 'Standby'){
            $isStartday = true;
            $isAfk =false;
         }
         else if($absensi_today->status == 'Afk'){
            $isAfk = true;
            $isStartday = true;
         }
         else if ($absensi_today->status == 'Selesai'){
            $isStartday = false;
            $isAfk = false;
         }

         $afk_session = Afk::select('start_afk','end_afk', DB::raw('TIME_TO_SEC(TIMEDIFF(end_afk, start_afk)) AS total_durasi_afk_session'))
         ->where('start_afk', '>=', $row->start_day)
         ->where('end_afk', '<=', $end_day)
         ->whereNotNull('end_afk')
         ->where('user_id', $row->user_id)
         ->groupBy('start_afk','end_afk')
         ->get();

         
         $afk_data = [];

         foreach ($afk_session as $afk) {

         $total_afk_session = gmdate('H:i:s', $afk->total_durasi_afk_session);
         $start_afk_session =date('Y-m-d H:i:s', strtotime($afk->start_afk));
         $end_afk_session =date('Y-m-d H:i:s', strtotime($afk->end_afk));
        
         // add the data to the array
         $afk_data[] = [
             'start_afk' => $start_afk_session,
             'end_afk' => $end_afk_session,
             'total_afk' => $total_afk_session,
         ];
         }
     
      $data[] = [
         'absen_id' => $row->absen_id,
         'user_id' => $row->user_id,
         'name' => $row->name,
         'start_day' => $row->start_day,
         'end_day' => $end_day,
         'total_afk' => $total_afk,
         'total_jam_kerja' => $total_jam_kerja,
         'afk_session' =>  $afk_data,
         'created_at' => $row->created_at,
         'status' => $row->status,
        
     ];
     $data_today=[
        'start_day_today' => $start_day_today,
         'end_day_today' => $end_day_today,
         'total_afk_today' => $total_afk_today,
         'total_jam_kerja_today' => $total_jam_kerja_today,
         'isStartday' =>  $isStartday,
         'isAfk' => $isAfk,
     ];
    }
    
    if(empty($data)){
        return response()->json([
            'status' => 'error',
            'message' => 'data tidak di temukan',
            'error_code' => 'DATA_NOT_FOUND'
        ]);
    }else{
        return response()->json([
            'status' => 'success',
            'data_today' => $data_today,
            'data' => $data,
        ]);
    }
    }
    public function getAbsensibylead()
    {
        try {
            // kode yang mungkin menghasilkan kesalahan
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'error_code' => 'UNAUTHORIZED'
                ], 401);
                }
            $getiduser = $user->id;
            $getDivisi = $user->divisi_id;
            $listuser = User::select('id')
            ->where('divisi_id', $getDivisi)
            ->where('role_id', 4)
            ->get();
            $id_list = $listuser->pluck('id')->implode(',');
    
            $absensi = Absensi::where('user_id',$id_list)
            
            ->get();
    
    
            return response()->json([
                'status' => 'success',
                'data' => $absensi,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
        
       
    }
    
    public function addStartday(){
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
            }
        $getiduser = $user->id;

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$start_day = DB::table('absensis')
                ->where('user_id', $getiduser)
                ->where('start_day', '>',$today)
                
                ->first();


// dd($start_day);
            
        if($start_day){
            return response()->json([
                'status' => 'error',
                'message' => 'startday hari ini sudah dilakukan, anda tidak bisa melakukan startday lagi atau belum end day',
                'error_code' => 'START_DAY_ALREADY_EXIST'
            ]);
        }

        $timezone = 'Asia/Jakarta'; // ganti dengan timezone yang Anda inginkan
        $date = now($timezone);
        
        $absensi = new Absensi([
            'user_id' => $getiduser,
            'start_day' => $date,
            'status' => 'Standby'
        ]);
        
    $absensi->save();
    return response()->json(
        [   'status' => 'success',
            'data'=>$absensi
        ], 201);
    }

    public function addStartAfk()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }
    
        $absensi = Absensi::where('user_id', $user->id)
            ->whereNotNull('start_day')
            ->whereNull('end_day')
            ->latest()
            ->first();
    
        if (!$absensi) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active attendance found'
            ], 404);
        }
        $timezone = 'Asia/Jakarta'; // ganti dengan timezone yang Anda inginkan
        $date = now($timezone);
    
        $afk = new Afk([
            'user_id' => $user->id,
            'start_afk' => $date,
        ]);
    
        if ($afk->save()) {
            $absensi->status = 'Afk';
            $absensi->update();
    
            return response()->json(
                [   'status' => 'success',
                    'data'=>$afk,
                    'status2'=>$absensi->status
                ], 201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start AFK'
            ], 500);
        }
    }
    

    public function addEndAfk()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }
        $afk = Afk::where('user_id', $user->id)
            ->whereNull('end_afk')
            ->latest()
            ->first();

            $absensi = Absensi::where('user_id', $user->id)
            ->whereNull('end_day')
            ->latest()
            ->first();
    
        if (!$afk) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active attendance found'
            ], 404);
        }
        $timezone = 'Asia/Jakarta'; // ganti dengan timezone yang Anda inginkan
        $date = now($timezone);

        $afk->end_afk = $date;
        // $durationSeconds = $afk->start_afk->diffInSeconds($afk->end_afk);

        // $afk->total_afk = $durationSeconds;
        $afk->update();

        $absensi->status = 'Standby';
        $absensi->update();
        
        return response()->json(
            [   'status' => 'success',
                'status_user'=>$absensi->status,
                'data'=>$afk,
            ], 201);
        
    }
    

    public function addEndday()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }
        $absensi = Absensi::where('user_id', $user->id)
            ->whereNull('end_day')
            ->latest()
            ->first();
        // $totalAfk = Afk::where('user_id', $user->id)
        //     ->whereNotNull('end_afk')
        //     ->sum('total_afk');
    
        if (!$absensi) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active attendance found'
            ], 404);
        }

        $timezone = 'Asia/Jakarta'; // ganti dengan timezone yang Anda inginkan
        $date = now($timezone);
        $absensi->end_day = $date;
        // $durationSeconds = $absensi->start_day->diffInSeconds($absensi->end_day);

        // $totalAfk = Afk::where('user_id', $user->id)
        //     ->whereNotNull('end_afk')
        //     ->where('start_afk', '>', $absensi->start_day)
        //     ->sum('total_afk');
            
        // $durationSeconds -= $totalAfk;
        // $absensi->total_afk = $totalAfk;
        // $absensi->total_jam_kerja = $durationSeconds;
        $absensi->status = 'Selesai';
        $absensi->update();
    
        return response()->json(
            [   'status' => 'success',
                'data'=>$absensi
            ], 201);
        
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
