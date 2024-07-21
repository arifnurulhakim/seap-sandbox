<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evaluasi;
use App\Models\Divisi;
use App\Models\Role;
use App\Models\User;
use App\Models\PeriodeEvaluasi;
use App\Models\Feedback;
use App\Models\NilaiEvaluasiUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluasiController extends Controller
{
    // Menampilkan semua data evaluasi
    public function index($user_id,$periode)
{
    // ambil data user yang sedang login
    $user = auth()->user();
    $reviewer_id = $user->id;
    if (!$user) {
        return response()->json([
        'status' => 'error',
        'message' => 'User not found',
        'error_code' => 'USER_NOT_FOUND'
        ], 404);
        }
        $user_reviewed = User::where('id',$user_id)->first();


        $nilaiEvaluasiUser = NilaiEvaluasiUser::where('user_id', $user_id)->first();
        $getiduser="";
        $getname="";
        if ($nilaiEvaluasiUser) {
            $getiduser = $nilaiEvaluasiUser->reviewer_id;
            $reviewer_name = User::where('id', $getiduser)->first();
            $getname = $reviewer_name->name;
            }
    $role ='';
    if ($user->role_id == 4){
        $role = 3;
    }else if ($user->role_id == 3){
        $role = $user_reviewed->role_id;
    };



        $latestPeriode1 = NilaiEvaluasiUser::where('user_id', $user_id)
        ->max('periode');
        $latestPeriode=$latestPeriode1;
        if($periode == 1){
            $latestPeriode = 1;
        }

        else if($periode == $latestPeriode1){
            $latestPeriode = $latestPeriode1 - 1;
        }

       else if($periode < $latestPeriode1){
            $latestPeriode = $latestPeriode1 - 2;
        }

        $feedback = Feedback::where('user_id',$user_id)
        ->where('reviewer_id', $reviewer_id)
        ->where('periode', $periode)
        ->wherebetween('periode',[$periode,$latestPeriode1])
        ->select('feedback','label','periode','reviewer_id')->get();
        $nilaiRataRata = NilaiEvaluasiUser::select(
            DB::raw('AVG(nilai) as nilai_rata_rata'),
            DB::raw('COUNT(nilai) as total_jumlah_nilai'),
            DB::raw('SUM(nilai) as total_nilai'),
            'periode_evaluasis.id as periode_id',
            'nilai_evaluasi_users.periode as periodeuser',
            'periode_evaluasis.periode as periode',
            'periode_evaluasis.label as label',
            'periode_evaluasis.isLock as isLock'
        )
        ->join('periode_evaluasis', 'periode_evaluasis.periode', '=', 'nilai_evaluasi_users.periode')
        ->where('nilai_evaluasi_users.user_id', $user_id)
        ->where('periode_evaluasis.user_id', $user_id)
        ->where('nilai_evaluasi_users.reviewer_id', $getiduser)
        ->whereBetween('periode_evaluasis.periode', [$periode, $latestPeriode1])
        ->groupBy( 'periode_evaluasis.periode', 'periode_evaluasis.label', 'periode_evaluasis.isLock','periode_evaluasis.id')
        ->get();


            $evaluasi = Evaluasi::where('role_id', $role)
            ->where(function ($query) use ($user) {
                $query->where('divisi_id', $user->divisi_id)
                      ->orWhereNull('divisi_id');
            })
            ->select('evaluasis.id as evaluasi_id', 'kategori', 'detail','keterangan');



            if($periode != 1){
                $evaluasi->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_1", [$reviewer_id,$user_id, $latestPeriode]);

                if ('reviewer_id_1'){
                $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_1) as name_reviewer_1");
                }

                $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_1", [$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT komentar FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as komentar_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_1",[$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_2", [$reviewer_id,$user_id, $latestPeriode]);

            if ('reviewer_id_2'){
            $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_2) as name_reviewer_2");
            }

            $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_2",[$reviewer_id,$user_id, $periode])
            ->selectRaw("(SELECT komentar FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as komentar_2",[$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_2", [$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_2", [$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_2",[$reviewer_id,$user_id, $periode]);
            if(is_null('nilai_1')){
                $progress='tetap';
            }
            else if ($periode == 1){
                $progress='nilai baru';
            }
            else if('nilai_1'>'nilai_2'){
                $progress='menurun';
            }else if('nilai_1'<'nilai_2'){
                $progress='meningkat';
            }else{
                $progress='tetap';
            }

            $evaluasi->selectRaw("('$progress') as progress");

            }else if ($periode == 1){
                $evaluasi->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_2", [$reviewer_id,$user_id, $latestPeriode]);

                if ('reviewer_id_2'){
                $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_2) as name_reviewer_2");
                }
                $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_2",[$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT komentar FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as komentar_2",[$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_2",[$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_2", [$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_2", [$reviewer_id,$user_id, $latestPeriode]);
                if(is_null('nilai_1')){
                    $progress='tetap';
                }
                else if ($periode == 1){
                    $progress='nilai baru';
                }
                else if('nilai_1'>'nilai_2'){
                    $progress='menurun';
                }else if('nilai_1'<'nilai_2'){
                    $progress='meningkat';
                }else{
                    $progress='tetap';
                }

                $evaluasi->selectRaw("('$progress') as progress");

            }
            $evaluasi= $evaluasi->get();
            $data = [
                'feedback' =>$feedback,
                'rata_rata' =>$nilaiRataRata,
                'evaluasi' =>$evaluasi
            ];
            return response()->json([
                'status' => 'success',
                'user_id' => $user_id,
                'reviewer_id' => $getiduser,
                'reviewer_name' => $getname,
                'max_periode' => $latestPeriode1,
                'periode' => $periode,
                'data' => $data
            ]);
}

public function result($user_id){
    $user = auth()->user();
    if (!$user) {
        return response()->json([
        'status' => 'error',
        'message' => 'User not found'
        ], 404);
        }
        $user_reviewed = User::where('id',$user_id)->first();
        $role ='';
        if($user_id == $user->id){
            $role = $user->role_id;
        }
        else if ($user->role_id == 4){
            $role = 3;
        }
        else if ($user->role_id == 3){
            $role = $user_reviewed->role_id;
        };

        $review_user = User::where('id', $user_id)->first();
        $role_id = $review_user->role_id;
        $get_role_name = Role::where('id',$role_id)->first();
        $role_name = $get_role_name->name;
        $getname_user = $review_user->name;
        $getdivisi="";
        $getdivisiName = "";
        if($role == 3){
            $getdivisi = NULL;
            $getdivisiName = Divisi::select('name')->where('id',$user->divisi_id)->first();
            $getdivisiName = $getdivisiName->name;
        }else{
            $getdivisi = $review_user->divisi_id;
            $getdivisiName = Divisi::select('name')->where('id',$getdivisi)->first();
            $getdivisiName = $getdivisiName->name;
        }
        // $getdivisi = $review_user->divisi_id;
        // $getdivisiName = Divisi::select('name')->where('id',$getdivisi)->first();
        // $getdivisiName = $getdivisiName->name;
        $getrole = $review_user->role_id;
        $nilaiEvaluasiUser = NilaiEvaluasiUser::where('user_id', $user_id)->first();
        $getiduser="";
        $getname="";
        $review_role_name="";
        if ($nilaiEvaluasiUser) {
            $getiduser = $nilaiEvaluasiUser->reviewer_id;
            $reviewer_name = User::where('id', $getiduser)->first();
            $review_role_id = $reviewer_name->role_id;
            $review_role = Role::where('id',$review_role_id)->first();
            $review_role_name =  $review_role->name;
            $getname = $reviewer_name->name;

            $nama_arr = explode(" ", $getname);
            $inisial = '';
            foreach ($nama_arr as $nama) {
                $inisial .= substr($nama, 0, 1);
            }
            $inisial = strtoupper($inisial);
            }

        // query untuk mengambil data evaluasi sesuai role_id dan divisi_id dari user yang sedang login
        $latestPeriode = NilaiEvaluasiUser::where('user_id', $user_id)
        ->max('periode');
        $periode = '';
        if ($latestPeriode == 1){
            $periode = '';

        }else{
        $periode=$latestPeriode - 1;
        }

        if(!$latestPeriode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Result Not Found nilai',
                'error_code' => 'RESULT_NOT_FOUND'
                ], 404);
        }
        $nilaiRataRata = NilaiEvaluasiUser::select(
            DB::raw('AVG(nilai) as nilai_rata_rata'),
            DB::raw('COUNT(nilai) as total_jumlah_nilai'),
            DB::raw('SUM(nilai) as total_nilai'),
            'periode_evaluasis.id as periode_id',
            'nilai_evaluasi_users.periode as periodeuser',
            'periode_evaluasis.periode as periode',
            'periode_evaluasis.label as label',
            'periode_evaluasis.isLock as isLock'
        )
        ->join('periode_evaluasis', 'periode_evaluasis.periode', '=', 'nilai_evaluasi_users.periode')
        ->where('nilai_evaluasi_users.user_id', $user_id)
        ->where('periode_evaluasis.user_id', $user_id)
        ->whereBetween('periode_evaluasis.periode', [$periode, $latestPeriode])
        ->groupBy( 'periode_evaluasis.periode', 'periode_evaluasis.label', 'periode_evaluasis.isLock','periode_evaluasis.id')
        ->get();



    $dataLead = DB::table('nilai_evaluasi_users')
        ->select('nilai_evaluasi_users.reviewer_id', 'users.name','nilai_evaluasi_users.periode')
        ->distinct()
        ->where('nilai_evaluasi_users.user_id', '=', $user_id)
        ->leftJoin('users', 'users.id', '=', 'nilai_evaluasi_users.reviewer_id')

        ->get()->toArray();

    $reviewers = [];
    foreach ($dataLead as $lead) {
        $reviewers[$lead->reviewer_id] = $lead->name;
    }
    $list_lead = NilaiEvaluasiUser::select('periode','reviewer_id')->where('user_id', $user_id)
    ->wherebetween('periode', [$periode, $latestPeriode])
    ->groupby ('periode','reviewer_id')
    ->get();

    $nilaiRataRataWithReviewers = [];

    foreach ($nilaiRataRata as $data) {
        $reviewerCount = 0;
        foreach ($dataLead as $lead) {
            if ($lead->periode == $data->periode) {
                $reviewerCount++;
            }
        }

        $reviewerNames = [];
        for ($i = 1; $i <= $reviewerCount; $i++) {
            $reviewerNames["reviewer_name_$i"] = '';
        }

        $nilaiRataRataWithReviewers[] = [
            'periode_id' => $data->periode_id,
            'periode' => $data->periode,
            'label' => $data->label,
            'nilai_rata_rata' => number_format($data->nilai_rata_rata, 1, '.', ''),
            'total_jumlah_nilai' => $data->total_jumlah_nilai,
            'total_nilai' => number_format($data->total_nilai, 1, '.', ''),
            ...$reviewerNames,
        ];

        $index = count($nilaiRataRataWithReviewers) - 1;
        $reviewerIndex = 1;
        foreach ($dataLead as $z => $lead) {
            if ($lead->periode == $data->periode) {
                $nilaiRataRataWithReviewers[$index]["reviewer_name_$reviewerIndex"] = $lead->name;
                $reviewer_inisial = '';
                $reviewer_name_arr = explode(' ', $lead->name);
                foreach ($reviewer_name_arr as $nama) {
                    $reviewer_inisial .= substr($nama, 0, 1);
                }
                $nilaiRataRataWithReviewers[$index]["reviewer_inisial_$reviewerIndex"] = strtoupper($reviewer_inisial);
                $reviewerIndex++;
            }
        }
    }
            $evaluasi = Evaluasi::where('role_id', $role)
            ->where('divisi_id', $getdivisi)
            ->pluck('id')
            ->toArray();
            if(!$evaluasi)
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'result not found evaluasi',
                    'error_code' => 'RESULT_NOT_FOUND'
                    ], 404);
            }

            $nilaievaluasiuser1 = Evaluasi::leftJoin('nilai_evaluasi_users', function($join) use ($periode, $user_id) {
                $join->on('evaluasis.id', '=', 'nilai_evaluasi_users.evaluasi_id')
                    ->where('nilai_evaluasi_users.periode', '=', $periode)
                    ->where('nilai_evaluasi_users.user_id', '=', $user_id);
            })->leftJoin('users', 'nilai_evaluasi_users.reviewer_id', '=', 'users.id')
            ->where('evaluasis.role_id', '=', $role)
            ->where('evaluasis.divisi_id', '=', $getdivisi)
            ->select('evaluasis.id as id_evaluasi', 'nilai_evaluasi_users.*', 'nilai_evaluasi_users.nilai as nilai','users.name as reviewer_name')
            ->get();

            $nilaievaluasiuser2 = Evaluasi::leftJoin('nilai_evaluasi_users', function($join) use ($latestPeriode, $user_id) {
                $join->on('evaluasis.id', '=', 'nilai_evaluasi_users.evaluasi_id')
                    ->where('nilai_evaluasi_users.periode', '=', $latestPeriode)
                    ->where('nilai_evaluasi_users.user_id', '=', $user_id);
            })->leftJoin('users', 'nilai_evaluasi_users.reviewer_id', '=', 'users.id')
            ->where('evaluasis.role_id', '=', $role)
            ->where('evaluasis.divisi_id', '=', $getdivisi)
            ->select('evaluasis.id as id_evaluasi', 'nilai_evaluasi_users.*', 'nilai_evaluasi_users.nilai as nilai','users.name as reviewer_name')
            ->get();
            $feedback = DB::table('feedback')
            ->select('periode')
            ->where('user_id', $user_id)
            ->wherebetween('periode',[$periode,$latestPeriode])
            ->groupBy('periode')
            ->get();
            $feedback1 = DB::table('feedback')->leftJoin('users', 'feedback.reviewer_id', '=', 'users.id')
            ->select('feedback.feedback', 'feedback.label', 'feedback.periode', 'feedback.reviewer_id','users.name as reviewer_name')
            ->where('user_id', $user_id)
            ->where('periode',$periode)
            ->orderBy('periode')
            ->get();

            $feedback2 = DB::table('feedback')->leftJoin('users', 'feedback.reviewer_id', '=', 'users.id')
            ->select('feedback.feedback', 'feedback.label', 'feedback.periode', 'feedback.reviewer_id','users.name as reviewer_name')
            ->where('user_id', $user_id)
            ->where('periode',$latestPeriode)
            ->orderBy('periode')
            ->get();
            $data_feedback = [];
            $fb = 1;
                $fb2 = 1;
                foreach ($feedback2 as $fc2) {
                    $data_feedback['periode_'.'1'.'_'.$fb2] = $fc2->periode ?? null;
                    $data_feedback['reviewer_id_'.'1'.'_'.$fb2] = $fc2->reviewer_id ?? null;
                    $data_feedback['reviewer_name_'.'1'.'_'.$fb2] = $fc2->reviewer_name ?? null;

                    $reviewer_name_fb_arr2 = explode(" ", $fc2->reviewer_name ?? '');
                    $reviewer_fb_inisial2 = '';
                    foreach ($reviewer_name_fb_arr2 as $nama_fb2) {
                        $reviewer_fb_inisial2 .= substr($nama_fb2, 0, 1);
                    }

                    $data_feedback['reviewer_inisial_'.'1'.'_'.$fb2] = strtoupper($reviewer_fb_inisial2);

                    $data_feedback['feedback_'.'1'.'_'.$fb2] = $fc2->feedback ?? null;

                    $fb2++;
                }
        $evaluasi1 = Evaluasi::where('role_id', $role)
            ->where('divisi_id', $getdivisi)
            ->get();

            if(!$evaluasi1)
            {
                return response()->json([
                    'status' => 'error',
                    'message' => 'result not found',
                    'error_code' => 'RESULT_NOT_FOUND'
                    ], 404);
            }

            $hasil_loop = array();


            foreach ($evaluasi1 as $eva) {
                $data_eva = array();
                $data_eva['kategori'] = $eva->kategori;
                $data_eva['detail'] = $eva->detail;
                $data_eva['keterangan'] = $eva->keterangan;

                $i = 1;
                $hasil_loop = array();
                $k = 1;
                $l = 1;
                $m =1;
                foreach ($evaluasi1 as $eva) {
                    $data_eva = array();
                    $data_eva['evaluasi_id'] = $eva->id;
                    $data_eva['kategori'] = $eva->kategori;
                    $data_eva['detail'] = $eva->detail;
                    $data_eva['keterangan'] = $eva->keterangan;

                    $i = 1;
                $j = 1;

                foreach ($nilaievaluasiuser1 as $nilai1) {
                    if ($nilai1->id_evaluasi == $eva->id) {
                        $data_eva['periode_1_'.$k] = $nilai1->periode ?? null;
                        $data_eva['label_1_'.$k] = $nilai1->label ?? null;
                        $data_eva['reviewer_id_1_'.$k.'_'.$i] = $nilai1->reviewer_id ?? null;
                        $data_eva['reviewer_name_1_'.$k.'_'.$i] = $nilai1->reviewer_name ?? null;

                        $reviewer_name_arr1 = explode(" ", $nilai1->reviewer_name ?? '');
                        $reviewer_inisial1 = '';
                        foreach ($reviewer_name_arr1 as $nama1) {
                            $reviewer_inisial1 .= substr($nama1, 0, 1);
                        }

                        $data_eva['reviewer_inisial_1_'.$k.'_'.$i] = strtoupper($reviewer_inisial1);

                        $data_eva['nilai_1_'.$k.'_'.$i] = $nilai1->nilai !== null ? number_format($nilai1->nilai, 1, '.', '') : null;
                        $data_eva['komentar_1_'.$k.'_'.$i] = $nilai1->komentar ?? null;
                        $getratarata1 = NilaiEvaluasiUser::where('periode', $periode)
                            ->where('user_id', $user_id)
                            ->where('evaluasi_id', $data_eva['evaluasi_id'])
                            ->avg('nilai');
                            $data_eva['nilai_rata_rata_1_'.$k] = $getratarata1 !== null ? number_format($getratarata1, 1, '.', '') : null;

                        $i++;
                    }
                }

                    foreach ($nilaievaluasiuser2 as $nilai2) {
                        if ($nilai2->id_evaluasi == $eva->id) {
                            $data_eva['periode_2_'.$l] = $nilai2->periode ?? null;
                            $data_eva['label_2_'.$l] = $nilai2->label ?? null;
                            $data_eva['reviewer_id_2_'.$l.'_'.$j] = $nilai2->reviewer_id ?? null;
                            $data_eva['reviewer_name_2_'.$l.'_'.$j] = $nilai2->reviewer_name ?? null;

                            $reviewer_name_arr2 = explode(" ", $nilai2->reviewer_name ?? '');
                            $reviewer_inisial2 = '';
                            foreach ($reviewer_name_arr2 as $nama2) {
                                $reviewer_inisial2 .= substr($nama2, 0, 1);
                            }

                            $data_eva['reviewer_inisial_2_'.$l.'_'.$j] = strtoupper($reviewer_inisial2);

                            $data_eva['nilai_2_'.$l.'_'.$j] = $nilai2->nilai !== null ? number_format($nilai2->nilai, 1, '.', '') : null;
                            $data_eva['komentar_2_'.$l.'_'.$j] = $nilai2->komentar ?? null;
                            $getratarata2 = NilaiEvaluasiUser::where('periode', $latestPeriode)
                            ->where('user_id', $user_id)
                            ->where('evaluasi_id', $data_eva['evaluasi_id'])
                            ->avg('nilai');
                            $data_eva['nilai_rata_rata_2_'.$l] = $getratarata2 !== null ? number_format($getratarata2, 1, '.', '') : null;

                            $j++;
                        }
                    }
                    if ($data_eva['nilai_rata_rata_2_'.$l]>$data_eva['nilai_rata_rata_1_'.$k]){
                        $data_eva['progress']="meningkat";
                    }
                else if ($data_eva['nilai_rata_rata_2_'.$l]<$data_eva['nilai_rata_rata_1_'.$k]){
                        $data_eva['progress']="menurun";
                    }
                    else if ($data_eva['nilai_rata_rata_2_'.$l]==$data_eva['nilai_rata_rata_1_'.$k]){
                        $data_eva['progress']="tetap";
                    }
                    $hasil_loop[] = $data_eva;
                }
                }
                                $data = [
                                    'feedback' =>$data_feedback,
                                    'rata_rata' =>$nilaiRataRataWithReviewers,
                                    'evaluasi' =>$hasil_loop,
                                ];
                                if ($data){
                                return response()->json([
                                    'status' => 'success',
                                    'user_id' => $user_id,
                                    'username' => $getname_user,
                                    'divisi_id' => $getdivisi,
                                    'divisi_name' => $getdivisiName,
                                    'role_name' => $role_name,
                                    'reviewer_id' => $getiduser,
                                    'reviewer_name' => $getname,
                                    'reviewer_role' => $review_role_name,
                                    'max_periode' => $latestPeriode,
                                    'periode' => $periode,
                                    'data' => $data
                                ]);
                            }else{
                                return response()->json([
                                    'status' => 'error',
                                    'message' => 'result not found',
                                    'error_code' => 'RESULT_NOT_FOUND'
                                    ], 404);
                            }   
}
public function result2($user_id){
    $user = auth()->user();
    if (!$user) {
        return response()->json([
        'status' => 'error',
        'message' => 'User not found'
        ], 404);
        }
        $review_user = User::where('id', $user_id)->first();
        $role_id = $review_user->role_id;
        $get_role_name = Role::where('id',$role_id)->first();
        $role_name = $get_role_name->name;
        $getname_user = $review_user->name;
        $getdivisi="";
        $getdivisiName = "";
        if($review_user->role_id == 3){
            $getdivisi = NULL;
            $getdivisiName = Divisi::select('name')->where('id',$review_user->divisi_id)->first();
            $getdivisiName = $getdivisiName->name;
        }else{
            $getdivisi = $review_user->divisi_id;
            $getdivisiName = Divisi::select('name')->where('id',$getdivisi)->first();
            $getdivisiName = $getdivisiName->name;
        }
        $getrole = $review_user->role_id;
        $nilaiEvaluasiUser = NilaiEvaluasiUser::where('user_id', $user_id)->first();
        $getiduser="";
        $getname="";
        $review_role_name="";
        if ($nilaiEvaluasiUser) {
            $getiduser = $nilaiEvaluasiUser->reviewer_id;
            $reviewer_name = User::where('id', $getiduser)->first();
            $review_role_id = $reviewer_name->role_id;
            $review_role = Role::where('id',$review_role_id)->first();
            $review_role_name =  $review_role->name;
            $getname = $reviewer_name->name;

            $nama_arr = explode(" ", $getname);
            $inisial = '';
            foreach ($nama_arr as $nama) {
                $inisial .= substr($nama, 0, 1);
            }
            $inisial = strtoupper($inisial);
            }

        // query untuk mengambil data evaluasi sesuai role_id dan divisi_id dari user yang sedang login
        $latestPeriode = NilaiEvaluasiUser::where('user_id', $user_id)
        ->max('periode');
        $periode = '';
        if ($latestPeriode == 1){
            $periode = '';

        }else{
        $periode=$latestPeriode - 1;
        }

        if(!$latestPeriode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Result Not Found nilai',
                'error_code' => 'RESULT_NOT_FOUND'
                ], 404);
        }
        $nilaiRataRata = NilaiEvaluasiUser::select(
            DB::raw('AVG(nilai) as nilai_rata_rata'),
            DB::raw('COUNT(nilai) as total_jumlah_nilai'),
            DB::raw('SUM(nilai) as total_nilai'),
            'periode_evaluasis.id as periode_id',
            'nilai_evaluasi_users.periode as periodeuser',
            'periode_evaluasis.periode as periode',
            'periode_evaluasis.label as label',
            'periode_evaluasis.isLock as isLock'
        )
        ->join('periode_evaluasis', 'periode_evaluasis.periode', '=', 'nilai_evaluasi_users.periode')
        ->where('nilai_evaluasi_users.user_id', $user_id)
        ->where('periode_evaluasis.user_id', $user_id)
        ->whereBetween('periode_evaluasis.periode', [$periode, $latestPeriode])
        ->groupBy( 'periode_evaluasis.periode', 'periode_evaluasis.label', 'periode_evaluasis.isLock','periode_evaluasis.id')
        ->get();

        // dd($nilaiRataRata);

    $dataLead = DB::table('nilai_evaluasi_users')
        ->select('nilai_evaluasi_users.reviewer_id', 'users.name','nilai_evaluasi_users.periode')
        ->distinct()
        ->where('nilai_evaluasi_users.user_id', '=', $user_id)
        ->leftJoin('users', 'users.id', '=', 'nilai_evaluasi_users.reviewer_id')

        ->get()->toArray();

    $reviewers = [];
    foreach ($dataLead as $lead) {
        $reviewers[$lead->reviewer_id] = $lead->name;
    }
    $list_lead = NilaiEvaluasiUser::select('periode','reviewer_id')->where('user_id', $user_id)
    ->wherebetween('periode', [$periode, $latestPeriode])
    ->groupby ('periode','reviewer_id')
    ->get();

    $nilaiRataRataWithReviewers = [];

foreach ($nilaiRataRata as $data) {
    $reviewerCount = 0;
    foreach ($dataLead as $lead) {
        if ($lead->periode == $data->periode) {
            $reviewerCount++;
        }
    }

    $reviewerNames = [];
    for ($i = 1; $i <= $reviewerCount; $i++) {
        $reviewerNames["reviewer_name_$i"] = '';
    }

    $nilaiRataRataWithReviewers[] = [
        'id' => $data->periode_id,
        'periode' => $data->periode,
        'label' => $data->label,
        'isLock' => $data->isLock,
        'nilai_rata_rata' => number_format($data->nilai_rata_rata, 1, '.', ''),
        'total_jumlah_nilai' => $data->total_jumlah_nilai,
        'total_nilai' => number_format($data->total_nilai, 1, '.', ''),
        ...$reviewerNames,
    ];

    $index = count($nilaiRataRataWithReviewers) - 1;
    $reviewerIndex = 1;
    foreach ($dataLead as $z => $lead) {
        if ($lead->periode == $data->periode) {
            $nilaiRataRataWithReviewers[$index]["reviewer_name_$reviewerIndex"] = $lead->name;
            $reviewer_inisial = '';
            $reviewer_name_arr = explode(' ', $lead->name);
            foreach ($reviewer_name_arr as $nama) {
                $reviewer_inisial .= substr($nama, 0, 1);
            }
            $nilaiRataRataWithReviewers[$index]["reviewer_inisial_$reviewerIndex"] = strtoupper($reviewer_inisial);
            $reviewerIndex++;
        }
    }
}


        $evaluasi = Evaluasi::where('role_id', $getrole)
        ->where('divisi_id', $getdivisi)
        ->pluck('id')
        ->toArray();
        if(!$evaluasi)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'result not found evaluasi',
                'error_code' => 'RESULT_NOT_FOUND'
                ], 404);
        }

        $nilaievaluasiuser1 = Evaluasi::leftJoin('nilai_evaluasi_users', function($join) use ($periode, $user_id) {
            $join->on('evaluasis.id', '=', 'nilai_evaluasi_users.evaluasi_id')
                ->where('nilai_evaluasi_users.periode', '=', $periode)
                ->where('nilai_evaluasi_users.user_id', '=', $user_id);
        })->leftJoin('users', 'nilai_evaluasi_users.reviewer_id', '=', 'users.id')
        ->where('evaluasis.role_id', '=', $getrole)
        ->where('evaluasis.divisi_id', '=', $getdivisi)
        ->select('evaluasis.id as id_evaluasi', 'nilai_evaluasi_users.*', 'nilai_evaluasi_users.nilai as nilai','users.name as reviewer_name')
        ->get();

        $nilaievaluasiuser2 = Evaluasi::leftJoin('nilai_evaluasi_users', function($join) use ($latestPeriode, $user_id) {
            $join->on('evaluasis.id', '=', 'nilai_evaluasi_users.evaluasi_id')
                ->where('nilai_evaluasi_users.periode', '=', $latestPeriode)
                ->where('nilai_evaluasi_users.user_id', '=', $user_id);
        })->leftJoin('users', 'nilai_evaluasi_users.reviewer_id', '=', 'users.id')
        ->where('evaluasis.role_id', '=', $getrole)
        ->where('evaluasis.divisi_id', '=', $getdivisi)
        ->select('evaluasis.id as id_evaluasi', 'nilai_evaluasi_users.*', 'nilai_evaluasi_users.nilai as nilai','users.name as reviewer_name')
        ->get();
        $feedback = DB::table('feedback')
        ->select('periode')
        ->where('user_id', $user_id)
        ->wherebetween('periode',[$periode,$latestPeriode])
        ->groupBy('periode')
        ->get();
        // dd($feedback);

        $feedback1 = DB::table('feedback')->leftJoin('users', 'feedback.reviewer_id', '=', 'users.id')
        ->select('feedback.feedback', 'feedback.label', 'feedback.periode', 'feedback.reviewer_id','users.name as reviewer_name')
        ->where('user_id', $user_id)
        ->where('periode',$periode)
        ->orderBy('periode')
        ->get();
        $feedback2 = DB::table('feedback')->leftJoin('users', 'feedback.reviewer_id', '=', 'users.id')
        ->select('feedback.feedback', 'feedback.label', 'feedback.periode', 'feedback.reviewer_id','users.name as reviewer_name')
        ->where('user_id', $user_id)
        ->where('periode',$latestPeriode)
        ->orderBy('periode')
        ->get();
        $data_feedback = [];
        $fb = 1;
        $fb3 =1;
            $fb2 = 1;
            foreach ($feedback2 as $fc2) {
                $data_feedback['periode_'.'1'.'_'.$fb2] = $fc2->periode ?? null;
                $data_feedback['reviewer_id_'.'1'.'_'.$fb2] = $fc2->reviewer_id ?? null;
                $data_feedback['reviewer_name_'.'1'.'_'.$fb2] = $fc2->reviewer_name ?? null;

                $reviewer_name_fb_arr2 = explode(" ", $fc2->reviewer_name ?? '');
                $reviewer_fb_inisial2 = '';
                foreach ($reviewer_name_fb_arr2 as $nama_fb2) {
                    $reviewer_fb_inisial2 .= substr($nama_fb2, 0, 1);
                }

                $data_feedback['reviewer_inisial_'.'1'.'_'.$fb2] = strtoupper($reviewer_fb_inisial2);

                $data_feedback['feedback_'.'1'.'_'.$fb2] = $fc2->feedback ?? null;

                $fb2++;
            }

        //     $fb++;
        // }

//    dd($data_feedback);
    $evaluasi1 = Evaluasi::where('role_id', $getrole)
        ->where('divisi_id', $getdivisi)
        ->get();

        if(!$evaluasi1)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'result not found',
                'error_code' => 'RESULT_NOT_FOUND'
                ], 404);
        }

    // dd($evaluasi1);


        $hasil_loop = array();


        foreach ($evaluasi1 as $eva) {
            $data_eva = array();
            $data_eva['kategori'] = $eva->kategori;
            $data_eva['detail'] = $eva->detail;
            $data_eva['keterangan'] = $eva->keterangan;

            $i = 1;
            $hasil_loop = array();
            $k = 1;
            $l = 1;
            $m =1;
            foreach ($evaluasi1 as $eva) {
                $data_eva = array();
                $data_eva['evaluasi_id'] = $eva->id;
                $data_eva['kategori'] = $eva->kategori;
                $data_eva['detail'] = $eva->detail;
                $data_eva['keterangan'] = $eva->keterangan;

                $i = 1;
            $j = 1;

            foreach ($nilaievaluasiuser1 as $nilai1) {
                if ($nilai1->id_evaluasi == $eva->id) {
                    $data_eva['periode_1_'.$k] = $nilai1->periode ?? null;
                    $data_eva['label_1_'.$k] = $nilai1->label ?? null;
                    $data_eva['reviewer_id_1_'.$k.'_'.$i] = $nilai1->reviewer_id ?? null;
                    $data_eva['reviewer_name_1_'.$k.'_'.$i] = $nilai1->reviewer_name ?? null;

                    $reviewer_name_arr1 = explode(" ", $nilai1->reviewer_name ?? '');
                    $reviewer_inisial1 = '';
                    foreach ($reviewer_name_arr1 as $nama1) {
                        $reviewer_inisial1 .= substr($nama1, 0, 1);
                    }

                    $data_eva['reviewer_inisial_1_'.$k.'_'.$i] = strtoupper($reviewer_inisial1);

                    $data_eva['nilai_1_'.$k.'_'.$i] = $nilai1->nilai !== null ? number_format($nilai1->nilai, 1, '.', '') : null;
                    $data_eva['komentar_1_'.$k.'_'.$i] = $nilai1->komentar ?? null;
                    $getratarata1 = NilaiEvaluasiUser::where('periode', $periode)
                        ->where('user_id', $user_id)
                        ->where('evaluasi_id', $data_eva['evaluasi_id'])
                        ->avg('nilai');
                        $data_eva['nilai_rata_rata_1_'.$k] = $getratarata1 !== null ? number_format($getratarata1, 1, '.', '') : null;

                    $i++;
                }
            }

                foreach ($nilaievaluasiuser2 as $nilai2) {
                    if ($nilai2->id_evaluasi == $eva->id) {
                        $data_eva['periode_2_'.$l] = $nilai2->periode ?? null;
                        $data_eva['label_2_'.$l] = $nilai2->label ?? null;
                        $data_eva['reviewer_id_2_'.$l.'_'.$j] = $nilai2->reviewer_id ?? null;
                        $data_eva['reviewer_name_2_'.$l.'_'.$j] = $nilai2->reviewer_name ?? null;

                        $reviewer_name_arr2 = explode(" ", $nilai2->reviewer_name ?? '');
                        $reviewer_inisial2 = '';
                        foreach ($reviewer_name_arr2 as $nama2) {
                            $reviewer_inisial2 .= substr($nama2, 0, 1);
                        }

                        $data_eva['reviewer_inisial_2_'.$l.'_'.$j] = strtoupper($reviewer_inisial2);

                        $data_eva['nilai_2_'.$l.'_'.$j] = $nilai2->nilai !== null ? number_format($nilai2->nilai, 1, '.', '') : null;
                        $data_eva['komentar_2_'.$l.'_'.$j] = $nilai2->komentar ?? null;
                        $getratarata2 = NilaiEvaluasiUser::where('periode', $latestPeriode)
                        ->where('user_id', $user_id)
                        ->where('evaluasi_id', $data_eva['evaluasi_id'])
                        ->avg('nilai');
                        $data_eva['nilai_rata_rata_2_'.$l] = $getratarata2 !== null ? number_format($getratarata2, 1, '.', '') : null;

                        $j++;
                    }
                }
                if ($data_eva['nilai_rata_rata_2_'.$l]>$data_eva['nilai_rata_rata_1_'.$k]){
                    $data_eva['progress']="meningkat";
                }
            else if ($data_eva['nilai_rata_rata_2_'.$l]<$data_eva['nilai_rata_rata_1_'.$k]){
                    $data_eva['progress']="menurun";
                }
                else if ($data_eva['nilai_rata_rata_2_'.$l]==$data_eva['nilai_rata_rata_1_'.$k]){
                    $data_eva['progress']="tetap";
                }
                $hasil_loop[] = $data_eva;
            }
            }

            $data = [
                'feedback' =>$data_feedback,
                'rata_rata' =>$nilaiRataRataWithReviewers,
                'evaluasi' =>$hasil_loop,
            ];
            if ($data){
            return response()->json([
                'status' => 'success',
                'user_id' => $user_id,
                'username' => $getname_user,
                'divisi_id' => $getdivisi,
                'divisi_name' => $getdivisiName,
                'role_name' => $role_name,
                'reviewer_id' => $getiduser,
                'reviewer_name' => $getname,
                'reviewer_role' => $review_role_name,
                'max_periode' => $latestPeriode,
                'periode' => $periode,
                'data' => $data
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'result not found',
                'error_code' => 'RESULT_NOT_FOUND'
                ], 404);
        }


}

public function getbyadmin($user_id,$periode)
{
    // ambil data user yang sedang login
    $user = auth()->user();

    if (!$user) {
        return response()->json([
        'status' => 'error',
        'message' => 'User not found',
        'error_code' => 'USER_NOT_FOUND'
        ], 404);
        }
        $review_user = User::where('id', $user_id)->first();
        $getdivisi = $review_user->divisi_id;
        $getrole = $review_user->role_id;

        $nilaiEvaluasiUser = NilaiEvaluasiUser::where('user_id', $user_id)->first();
        $reviewer_id = $nilaiEvaluasiUser->reviewer_id;
        $getiduser="";
        $getname="";
        if ($nilaiEvaluasiUser) {
            $getiduser = $nilaiEvaluasiUser->reviewer_id;
            $reviewer_name = User::where('id', $getiduser)->first();
            $getname = $reviewer_name->name;
            }


    $role ='';
    if ($user->role_id == 4){
        $role = 3;
    }else if ($user->role_id == 3){
        $role = 4;
    };

        $latestPeriode1 = NilaiEvaluasiUser::where('user_id', $user_id)
        ->max('periode');
        $latestPeriode=$latestPeriode1;
        if($periode == 1){
            $latestPeriode = 1;
        }

        else if($periode == $latestPeriode1){
            $latestPeriode = $latestPeriode1 - 1;
        }

       else if($periode < $latestPeriode1){
            $latestPeriode = $latestPeriode1 - 2;
        }


        $nilaiRataRata = NilaiEvaluasiUser::select('periode',  DB::raw('AVG(nilai) as nilai_rata_rata'), DB::raw('count(nilai) as total_jumlah_nilai'), DB::raw('SUM(nilai) as total_nilai'))
        ->where('reviewer_id', $getiduser)
        ->where('user_id', $user_id)
        ->wherebetween('periode',[$periode,$latestPeriode1])
        ->groupBy('periode')
        ->get();




            $evaluasi = Evaluasi::where('role_id', $getrole)
            ->where(function ($query) use ($user, $getdivisi) {
                $query->where('divisi_id', $getdivisi)
                      ->orWhereNull('divisi_id');
            })
            ->select('evaluasis.id as evaluasi_id', 'kategori', 'detail','keterangan');



            if($periode != 1){
                $evaluasi->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_1", [$reviewer_id,$user_id, $latestPeriode]);

                if ('reviewer_id_1'){
                $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_1) as name_reviewer_1");
                }

                $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_1", [$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_1",[$reviewer_id,$user_id, $latestPeriode])

            ->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_2", [$reviewer_id,$user_id, $latestPeriode]);

            if ('reviewer_id_2'){
            $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_2) as name_reviewer_2");
            }

            $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_2",[$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_2", [$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_2", [$reviewer_id,$user_id, $periode])

            ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_2",[$reviewer_id,$user_id, $periode]);
            if(is_null('nilai_1')){
                $progress='tetap';
            }
            else if ($periode == 1){
                $progress='nilai baru';
            }
            else if('nilai_1'>'nilai_2'){
                $progress='menurun';
            }else if('nilai_1'<'nilai_2'){
                $progress='meningkat';
            }else{
                $progress='tetap';
            }

            $evaluasi->selectRaw("('$progress') as progress");

            }else if ($periode == 1){
                $evaluasi->selectRaw("(SELECT reviewer_id FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as reviewer_id_2", [$reviewer_id,$user_id, $latestPeriode]);

                if ('reviewer_id_2'){
                $evaluasi->selectRaw("(SELECT name FROM pis_users WHERE id = reviewer_id_2) as name_reviewer_2");
                }
                $evaluasi->selectRaw("(SELECT nilai FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as nilai_2",[$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT AVG(nilai) FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as rata_rata_nilai_2",[$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT periode FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as periode_2", [$reviewer_id,$user_id, $latestPeriode])

                ->selectRaw("(SELECT label FROM pis_nilai_evaluasi_users WHERE evaluasi_id = pis_evaluasis.id AND reviewer_id = ? AND user_id = ? AND periode = ?) as label_2", [$reviewer_id,$user_id, $latestPeriode]);
                if(is_null('nilai_1')){
                    $progress='tetap';
                }
                else if ($periode == 1){
                    $progress='nilai baru';
                }
                else if('nilai_1'>'nilai_2'){
                    $progress='menurun';
                }else if('nilai_1'<'nilai_2'){
                    $progress='meningkat';
                }else{
                    $progress='tetap';
                }

                $evaluasi->selectRaw("('$progress') as progress");

            }
            $evaluasi= $evaluasi->get();
            $data = [
                'rata_rata' =>$nilaiRataRata,
                'evaluasi' =>$evaluasi
            ];
            return response()->json([
                'status' => 'success',
                'user_id' => $user_id,
                'reviewer_id' => $getiduser,
                'reviewer_name' => $getname,
                'max_periode' => $latestPeriode1,
                'periode' => $periode,
                'data' => $data
            ]);
}

public function getAllEvaluasi(){
    $nilai = NilaiEvaluasiUser::all();

    return response()->json([
        'status' => 'success',
        'data' => $nilai,
    ]);
}

    // Menyimpan data evaluasi baru
    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|numeric',
            'divisi_id' => 'required|numeric',
            'kategori' => 'required',
            'detail' => 'required',
            'keterangan' => 'required',
        ]);

        $evaluasi = Evaluasi::create($request->all());

        return response()->json([
            'message' => 'Evaluasi berhasil ditambahkan',
            'data' => $evaluasi
        ]);
    }

    // Menampilkan data evaluasi berdasarkan id
    public function show($id)
    {
        $evaluasi = Evaluasi::find($id);
        if ($evaluasi) {
            return response()->json($evaluasi);
        } else {
            return response()->json(['message' => 'Evaluasi tidak ditemukan'], 404);
        }
    }

    // Mengupdate data evaluasi berdasarkan id
    public function update(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'required|numeric',
            'divisi_id' => 'required|numeric',
            'kategori' => 'required',
            'detail' => 'required',
            'keterangan' => 'required',
        ]);

        $evaluasi = Evaluasi::find($id);
        if ($evaluasi) {
            $evaluasi->update($request->all());

            return response()->json([
                'message' => 'Evaluasi berhasil diupdate',
                'data' => $evaluasi
            ]);
        } else {
            return response()->json(['message' => 'Evaluasi tidak ditemukan'], 404);
        }
    }

    // Menghapus data evaluasi berdasarkan id
    public function destroy($id)
    {
        $evaluasi = Evaluasi::find($id);
        if ($evaluasi) {
            $evaluasi->delete();
            return response()->json(['message' => 'Evaluasi berhasil dihapus']);
        } else {
            return response()->json(['message' => 'Evaluasi tidak ditemukan'], 404);
        }
    }


    public function addNilai(Request $request, $user_id)
    {
        $user = Auth::user();
        $reviewer_id = $user->id;

        $validator = Validator::make($request->all(), [
            'periode' => ['required', 'numeric'],
            'data' => [
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $item) {
                        if (!isset($item['evaluasi_id'])) {
                            $fail('Setiap item harus memiliki evaluasi_id');
                        }

                        if (isset($item['nilai']) && ($item['nilai'] < 0 || $item['nilai'] > 5)) {
                            $fail('Nilai harus berada di antara 0 dan 5');
                        }
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR'
            ], 422);

        }


        // membuat array berisi data nilai evaluasi user
        $data = [];

        foreach ($request->data as $item) {
            $lastperiode = $request->periode - 1;
            // dd($lastperiode);
            $getnilaievaluasi = NilaiEvaluasiUser::select('nilai')->where('reviewer_id',$reviewer_id)
            ->where('periode', $lastperiode)
            ->where('evaluasi_id',$item['evaluasi_id'])->first();

            $getnilai ='';
            if($getnilaievaluasi == null||$getnilaievaluasi == ""){
                $getnilai = 0;
            }else{
            $getnilai = $getnilaievaluasi->nilai;
            }

            $data[] = [
                'user_id' => $user_id,
                'reviewer_id' => $reviewer_id,
                'evaluasi_id' => $item['evaluasi_id'],
                'nilai' => $item['nilai'] ? number_format($item['nilai'], 2, '.', '') : NULL,

                'periode' => $request->periode,
                'label' => $request->label,
                'komentar' => $item['komentar']
            ];
            $feedback = [
                'user_id' => $user_id,
                'reviewer_id' => $reviewer_id,
                'periode' => $request->periode,
                'label' => $request->label,
                'feedback' => $request->feedback
            ];
            $periode = [
                'user_id' => $user_id,
                'periode' => $request->periode
            ];
        }

        // melakukan multi-insert dengan menggunakan metode updateOrInsert()
        foreach ($data as $item) {
            NilaiEvaluasiUser::updateOrInsert(
                [
                    'user_id' => $user_id,
                    'reviewer_id' => $reviewer_id,
                    'evaluasi_id' => $item['evaluasi_id'],
                    'periode' => $request->periode,
                ],
                $item
            );
            Feedback::updateOrInsert(
                [
                    'user_id' => $user_id,
                    'reviewer_id' => $reviewer_id,
                    'periode' => $request->periode,
                ],
                $feedback
            );
            PeriodeEvaluasi::updateOrInsert(
                [
                    'user_id'=>$user_id,
                    'periode' => $request->periode,
                ],
                $periode
            );

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Evaluasi berhasil ditambahkan',
            'data' => $data
        ]);
    }
    public function review_nilai($user_id){
        $user = Auth::user();
        $getiduser = $user->id;
        $role ='';
        if ($user->role_id == 4){
            $role = 3;
        };
        if ($user->role_id == 3){
            $role = 4;
        };
        $nilaiEvaluasiUser = NilaiEvaluasiUser::where('reviewer_id', $getiduser)
                                                ->where('user_id',$user_id)
                                                ->get();

            if (!$nilaiEvaluasiUser) {
            return response()->json([
            'status' => 'error',
            'message' => 'User not found'
            ], 404);
            }

        $nilaiRataRata = NilaiEvaluasiUser::where('reviewer_id', $getiduser)
                                            ->where('user_id',$user_id)
                                            ->avg('nilai');

        $getevaluasi = Evaluasi::where('role_id',$role)
                                ->where('divisi_id', $user->divisi_id)
                                ->get();
        // dd($getevaluasi);
            $data = [
                'kategori' => $kategori,
                'getevaluasi' =>$getevaluasi

            ];
            return response()->json([
                'status' => 'success',
                'user_id' => $user_id,
                'reviewer_id' => $getiduser,
                'rata-rata'=> $nilaiRataRata,
                'data' => $data,
            ]);
    }
    public function notifeval(){
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }
        $getiduser = $user->id;
        $divisi = $user->divisi_id;
        if($user->role_id == 4){
            return response()->json([
                'error' => [
                    'code' => 403,
                    'message' => 'Access Denied',
                    'details' => [
                        'reason' => 'Invalid Credentials'
                    ]
                ]
            ], 403);
        }
        $role = 4;
        $today = date('Y-m-d');
        $thisYear = date('Y');
        $lastYear = date("Y", strtotime("-1 year"));
        define('EVALUATION_NOTIFICATION', '30');
            $get_user = DB::table('users')
            ->select('id', 'name', 'join_date',
            DB::raw('DATEDIFF(DATE_FORMAT(join_date, "'.$thisYear.'-%m-%d"), "'.$today.'") AS next_evaluation'),
            DB::raw('DATE_FORMAT(join_date, "'.$thisYear.'-%m-%d") AS evaluation_date'),
            DB::raw('DATE_FORMAT(join_date, "%e %M '.$thisYear.'") AS evaluation_date_string')
        )
        ->where(function ($query) use ($role,$divisi) {
            if ($divisi == 1||$divisi == 2){
                $query->whereNotIn('users.id', function($subquery) {
                    $subquery->select('user_id')->from('users_excludes');
                });
            }
            else if($divisi == 4){
                $query->where('role_id',$role);
            }
            else{
                $query->whereNotIn('users.id', function($subquery) {
                    $subquery->select('user_id')->from('users_excludes');
                })
                ->where('role_id',$role)
                ->where('divisi_id',$divisi);
            }
        })
        ->where('join_date', '<>', '0000-00-00')
        ->whereRaw('DATEDIFF(DATE_FORMAT(join_date, "'.$thisYear.'-%m-%d"), "'.$today.'") > 0')
        ->whereRaw('DATEDIFF(DATE_FORMAT(join_date, "'.$thisYear.'-%m-%d"), "'.$today.'") < '.EVALUATION_NOTIFICATION)
        ->union(DB::table('users')
            ->select('id', 'name', 'join_date',
                DB::raw('DATEDIFF(DATE_ADD(join_date, INTERVAL 3 MONTH), "'.$today.'") AS next_evaluation'),
                DB::raw('DATE_ADD(join_date, INTERVAL 3 MONTH) AS evaluation_date'),
                DB::raw('DATE_FORMAT(DATE_ADD(join_date, INTERVAL 3 MONTH), "%e %M %Y") AS evaluation_date_string')
            )
            ->where('join_date', '<>', '0000-00-00')
            ->whereRaw('DATEDIFF(DATE_ADD(join_date, INTERVAL 3 MONTH), "'.$today.'") > 0')
            ->whereRaw('DATEDIFF(DATE_ADD(join_date, INTERVAL 3 MONTH), "'.$today.'") < '.EVALUATION_NOTIFICATION)

            ->where(function ($query) use ($thisYear, $lastYear) {
                $query->whereRaw('DATE_FORMAT(join_date, "%Y") = "'.$thisYear.'"')
                ->orWhere(function ($query) use ($thisYear, $lastYear) {
                    $query->whereRaw('DATE_FORMAT(join_date, "%Y") = "'.$lastYear.'"')
                    ->whereRaw('DATE_FORMAT(join_date, "%m") >= "10"');
                });

            })
            ->where(function ($query) use ($role,$divisi) {
                if ($divisi == 1||$divisi == 2){
                    $query->whereNotIn('users.id', function($subquery) {
                        $subquery->select('user_id')->from('users_excludes');
                    })
                    ->where('role_id',$role);
                }
                else if($divisi == 4){
                    $query->where('role_id',$role);
                }
                else{
                    $query->whereNotIn('users.id', function($subquery) {
                        $subquery->select('user_id')->from('users_excludes');
                    })
                    ->where('role_id',$role)
                    ->where('divisi_id',$divisi);
                }
            })
        )
        ->orderBy('evaluation_date')
        ->get();
        // dd($get_user);

        if(!empty($get_user)){
            return response()->json([
                'status' => 'success',
                'status_evaluation' => true,
                'list_user' => $get_user
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'status_evaluation' => false,

            ]);
        }
    }

    public function updateLabel(Request $request, $id)
    {
        $request->validate([
            'label' => 'required',
            'isLock' => 'required',
        ]);

        $periode = PeriodeEvaluasi::find($id);
        if ($periode) {
            $periode->update($request->all());

            return response()->json([
                'message' => 'periode label berhasil diupdate',
                'status' => 'success',
                'data' => $periode
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'periode label tidak ditemukan',
                'error_code' => 'PERIODE_NOT_FOUND'
            ], 404);
        }
    }

}


