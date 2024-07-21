<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Afk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
class exportabsenController extends Controller
{

    
    // public function exportCSV()
    // {
    //     $data = Absensi::get(); // query data dari database
    //     $filename = 'absensi-' . date('Ymd') . '.csv'; // nama file CSV yang dihasilkan
    
    //     $headers = array(
    //         'Content-Type' => 'text/csv',
    //         'Content-Disposition' => 'attachment; filename="'.$filename.'"',
    //     );
    
    //     // buat file CSV
    //     $callback = function() use ($data) {
    //         $handle = fopen('php://output', 'w');
    //         fputcsv($handle, ['Absen ID', 'User ID', 'Start Day', 'End Day', 'Status', 'Created At']);
    //         foreach($data as $row) {
    //             fputcsv($handle, [$row->absen_id, $row->user_id, $row->start_day, $row->end_day, $row->status, $row->created_at]);
    //         }
    
    //         fclose($handle);
    //     };
    
    //     // $response = Response::stream($callback, 200, $headers); // kirimkan file CSV sebagai respon ke klien
    //     // $response = Response::stream($callback, 200, $headers); // kirimkan file CSV sebagai respon ke klien
    //     $response = Response::stream($callback, 200, $headers); // kirimkan file CSV sebagai respon ke klien
    //     $response->headers->set('Content-Type', 'text/csv'); // tambahkan header Content-Type
    //     $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"'); // tambahkan header Content-Disposition
    
    //     return $response;
    // }
    public function exportCSV()
{
    $data = Absensi::get(); // query data dari database
    $dateStart = date('Ymd');
    $filename = "plexus_absen_".$dateStart.".csv";
    $filename_path = '/home/doddiplexus/doddi.plexustechdev.com/plexus-system/api/public/csv/' . $filename;
    // path to save CSV file
    // $headers = array(
    //     'Content-Type' => 'text/csv',
    //     'Content-Disposition' => 'attachment; filename="'.$filename.'"',
    // );

    // buat file CSV
    $handle = fopen($filename_path, 'w');
    fputcsv($handle, ['Absen ID', 'User ID', 'Start Day', 'End Day', 'Status', 'Created At']);
    foreach($data as $row) {
        fputcsv($handle, [$row->absen_id, $row->user_id, $row->start_day, $row->end_day, $row->status, $row->created_at]);
    }
    fclose($handle);

    // Kasih balikan nama file & urlnya
    $filename_url = url('csv/'.$filename);
    return response()->json([
        'status' => 'SUCCESS',
        'filename' => $filename,
        'filename_url' => $filename_url,
    ]);
}
}