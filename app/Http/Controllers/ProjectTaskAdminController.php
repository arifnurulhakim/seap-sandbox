<?php

namespace App\Http\Controllers;

use App\Models\ProjectTaskSession;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectTaskAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Response
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        //
    }

    // public function getReport($id_user = '')
    // {
    //     $user = Auth::user();
    //     $cekuser = UserRole::where('id_user', $user->id)->first();
    //     $role_id = $cekuser->id_role;
    //     $filter = $request->input('filter', '');

    //     $dateStart = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $request->input('date_start')))
    //     ? $request->input('date_start')
    //     : date('Y-m-d', strtotime($request->input('date_start')));

    //     $dateEnd = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $request->input('date_end')))
    //     ? $request->input('date_end')
    //     : date('Y-m-d');

    //     $dateStart = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $get['date_start'])) ? $get['date_start'] : date('Y-m-d', strtotime($get['date_start'])); // date_start
    //     $dateEnd = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $get['date_end'])) ? $get['date_end'] : date('Y-m-d', strtotime($get['date_end'])); // date_end
    //     $idUser = $user->id; // id_user
    //     $export = ($get['export'] == 'csv' && (in_array('super_admin', $this->checkPermission($token)) || in_array('admin', $this->checkPermission($token)) || in_array('project_owner', $this->checkPermission($token)))) ? $get['export'] : ""; // export

    //     $db = $this->connectDb();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 'ERROR',
    //             'message' => 'Not Authorized',
    //             'error_code' => 'not_authorized',
    //         ], 403);
    //     } else {
    //         if ($idUser) {
    //             $interval = abs((strtotime($dateStart) - strtotime($dateEnd)) / (60 * 60 * 24));
    //             $userDuration = array();
    //             $durationSecondTotal = 0;
    //             $overtimeSecondTotal = 0;

    //             for ($i = 0; $i <= $interval; $i++) {
    //                 $date = date('Y-m-d', strtotime($dateStart . ' +' . $i . ' day'));
    //                 $dateString = date('j F Y', strtotime($dateStart . ' +' . $i . ' day'));
    //                 $task = ProjectTaskSession::where('id_artist', $idUser)
    //                     ->where('time_start', '>=', $dateStart)
    //                     ->where('time_end', '<=', $dateEnd)
    //                     ->get();
    //                 $duration = (is_array($task)) ? $task[0]['duration'] : '00:00:00';
    //                 $session = (is_array($task)) ? $task[0]['session'] : array();
    //                 $idArtist = (is_array($task)) ? $task[0]['id_artist'] : $idArtist;
    //                 $artist = (is_array($task)) ? $task[0]['artist'] : $artist;
    //                 $username = (is_array($task)) ? $task[0]['username'] : $username;
    //                 $isOvertime = (is_array($task)) ? $task[0]['is_overtime'] : '1';
    //                 $sql = (is_array($task)) ? $task[0]['sql'] : $task;

    //                 /* OVERTIME */
    //                 $overtimeSecond = 0;

    //                 for ($j = 0; $j < count($session); $j++) {
    //                     $sessionDuration = strtotime($session[$j]['time_end']) - strtotime($session[$j]['time_start']);
    //                     $overtimeSecond = ($session[$j]['is_overtime'] == '1') ? ($overtimeSecond + $sessionDuration) : $overtimeSecond;

    //                     $session[$j]['time_start_string'] = date('j M y H:i:s', strtotime($session[$j]['time_start']));
    //                     $session[$j]['time_end_string'] = date('j M y H:i:s', strtotime($session[$j]['time_end']));
    //                     $session[$j]['duration'] = sprintf('%02d:%02d:%02d', floor($sessionDuration / 3600), floor($sessionDuration / 60 % 60), floor($sessionDuration % 60));
    //                 }

    //                 sscanf($duration, "%d:%d:%d", $hours, $minutes, $seconds);
    //                 $durationSecond = ($hours * 3600) + ($minutes * 60) + $seconds;

    //                 $overtime = sprintf('%02d:%02d:%02d', floor($overtimeSecond / 3600), floor($overtimeSecond / 60 % 60), floor($overtimeSecond % 60));

    //                 $userDuration[] = array('sql' => $sql, 'report_date_string' => $dateString, 'report_date' => $date, 'report_duration' => $duration, 'report_duration_session' => $session, 'report_overtime' => $overtime, 'report_overtime_valid' => $isOvertime);

    //                 $durationSecondTotal += $durationSecond;
    //                 $overtimeSecondTotal += $overtimeSecond;
    //             }

    //             $dateStartString = date('j M y', strtotime($dateStart));
    //             $dateEndString = date('j M y', strtotime($dateEnd));
    //             $durationTotal = sprintf('%02d:%02d:%02d', floor($durationSecondTotal / 3600), floor($durationSecondTotal / 60 % 60), floor($durationSecondTotal % 60));
    //             $overtimeTotal = sprintf('%02d:%02d:%02d', floor($overtimeSecondTotal / 3600), floor($overtimeSecondTotal / 60 % 60), floor($overtimeSecondTotal % 60));

    //             if ($export == 'csv') {
    //                 foreach (glob("../media/oraylog_*.csv") as $f) {
    //                     unlink($f);
    //                 }

    //                 $filename = "oraylog_" . $dateStart . "_" . $dateEnd . "_" . $username . ".csv";
    //                 $filename_path = "../media/" . $filename;
    //                 $filename_url = MEDIA_URL . $filename;
    //                 $totalData = count($userDuration);
    //                 $csv_data = array(array('No', 'Date', 'Task (Real Time)', 'Overtime'));

    //                 for ($i = 1; $i <= $totalData; $i++) {
    //                     $csv_data[$i][0] = $i;
    //                     $csv_data[$i][1] = $userDuration[$i - 1]['report_date_string'];
    //                     $csv_data[$i][2] = $userDuration[$i - 1]['report_duration'];
    //                     $csv_data[$i][3] = $userDuration[$i - 1]['report_overtime'];
    //                 }

    //                 $csv_data[($totalData + 1)][0] = "";
    //                 $csv_data[($totalData + 1)][1] = "TOTAL";
    //                 $csv_data[($totalData + 1)][2] = $durationTotal;
    //                 $csv_data[($totalData + 1)][3] = $overtimeTotal;
    //                 $fp = fopen($filename_path, 'w');

    //                 foreach ($csv_data as $fields) {
    //                     fputcsv($fp, $fields, ";");
    //                 }

    //                 fclose($fp);

    //                 return response()->json(['status' => 'SUCCESS', 'filename' => $filename, 'filename_url' => $filename_url, 'csv_data' => $userDuration]);
    //             } else {
    //                 return response()->json(['status' => 'SUCCESS', 'interval' => $interval, 'date_start' => $dateStartString, 'date_end' => $dateEndString, 'id_artist' => $idArtist, 'artist' => $artist, 'report_duration_total' => $durationTotal, 'report_overtime_total' => $overtimeTotal, 'data' => $userDuration]);
    //             }
    //         } else {
    //             $task = $this->getUserTaskInterval($dateStart, $dateEnd);
    //             return response()->json(['status' => 'SUCCESS', 'data' => $task]);
    //         }
    //     }
    // }
    public function getReport(Request $request, $id_user = '')
    {

        $user = DB::table('user')
            ->join('user_profile', 'user.id', '=', 'user_profile.id_user')
            ->where('user.id', Auth::id())
            ->first();
        // dd($user);
        $cekuser = UserRole::where('id_user', $user->id)->first();
        $role_id = $cekuser->id_role;

        $dateStart = $request->filled('date_start')
        ? date('Y-m-d 00:00:01', strtotime($request->input('date_start')))
        : date('Y-m-d 00:00:01');

        $dateEnd = $request->filled('date_end')
        ? date('Y-m-d 23:59:59', strtotime($request->input('date_end')))
        : date('Y-m-d 23:59:59');

        $idUser = $user->id;
        $export = $request->filled('export') && in_array($request->input('export'), ['csv'])
        ? $request->input('export')
        : '';

        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        if ($idUser) {
            $interval = abs(strtotime($dateStart) - strtotime($dateEnd)) / (60 * 60 * 24);
            $userDuration = [];
            $task = ProjectTaskSession::where('id_artist', $idUser)
                ->where('time_start', '>=', $dateStart)
                ->where('time_end', '<=', $dateEnd)
                ->get();

            $durationSecondTotal = 0;
            $overtimeSecondTotal = 0;

            foreach ($task as $session) {
                // Assuming $session is an instance of ProjectTaskSession model
                $sessionDuration = strtotime($session->time_end) - strtotime($session->time_start);
                $overtimeSecond = $session->is_overtime == '1' ? $sessionDuration : 0;

                $durationSecondTotal += $sessionDuration;
                $overtimeSecondTotal += $overtimeSecond;
            }
            for ($i = 0; $i <= $interval; $i++) {
                $report_date = date('Y-m-d', strtotime("+$i day", strtotime($dateStart)));
                // dd($date);
                $report_date_string = date('j F Y', strtotime("+$i day", strtotime($dateStart)));

                $tasks = ProjectTaskSession::where('id_artist', $idUser)
                    ->where('time_start', '>=', $report_date . ' 00:00:00')
                    ->where('time_end', '<=', $report_date . ' 23:59:59')
                    ->selectRaw('*, SEC_TO_TIME(TIMESTAMPDIFF(SECOND, time_start, time_end)) AS duration, TIMESTAMPDIFF(SECOND, time_start, time_end) AS duration_in_seconds')
                    ->get();
                // dd($tasks);

                // Menghitung total durasi dari hasil query (dalam detik)
                $totalDurationInSeconds = $tasks->sum('duration_in_seconds');

                // Menyimpan total durasi dalam variabel report_duration
                $report_duration = gmdate('H:i:s', $totalDurationInSeconds);

                $report_duration_session = ProjectTaskSession::where('project_task_session.id_artist', $idUser)
                    ->where('project_task_session.time_start', '>=', $report_date . ' 00:00:00')
                    ->where('project_task_session.time_end', '<=', $report_date . ' 23:59:59')
                    ->join('project_task', 'project_task_session.id_task', '=', 'project_task.id')
                    ->join('project_subasset', 'project_task.id_subasset', '=', 'project_subasset.id') // Join dengan project_subasset
                    ->join('project_category', 'project_subasset.id_category', '=', 'project_category.id') // Join dengan project_category
                    ->join('project', 'project_category.id_project', '=', 'project.id') // Join dengan project
                    ->selectRaw('
                    pis_project_task_session.id as id_session,
                    pis_project_work.name AS project,
                    pis_project_task_session.time_start,
                    pis_project_task_session.time_end,
                    pis_project_task_session.is_overtime,
                    DATE_FORMAT(pis_project_task_session.time_start, "%e %b %y %T") AS time_start_string,
                    DATE_FORMAT(pis_project_task_session.time_end, "%e %b %y %T") AS time_end_string,
                    SEC_TO_TIME(TIMESTAMPDIFF(SECOND, pis_project_task_session.time_start, pis_project_task_session.time_end)) AS duration
                ')
                    ->get();

                // dd($session);
                $report_overtime_valid = $tasks->isEmpty() ? '1' : $tasks[0]->is_overtime;

                $overtimeSecond = 0;

                foreach ($report_duration_session as $s) {
                    $sessionDuration = strtotime($s->time_end) - strtotime($s->time_start);
                    $overtimeSecond += $s->is_overtime == '1' ? $sessionDuration : 0;
                    $s->time_start_string = date('j M y H:i:s', strtotime($s->time_start));
                    $s->time_end_string = date('j M y H:i:s', strtotime($s->time_end));
                    $s->duration = gmdate('H:i:s', $sessionDuration);
                }

                sscanf($report_duration, "%d:%d:%d", $hours, $minutes, $seconds);
                $durationSecond = ($hours * 3600) + ($minutes * 60) + $seconds;

                $report_overtime = gmdate('H:i:s', $overtimeSecond);

                $userDuration[] = compact('report_date_string', 'report_date', 'report_duration', 'report_duration_session', 'report_overtime', 'report_overtime_valid');

                // $durationSecondTotal += $durationSecond;
                // $overtimeSecondTotal += $overtimeSecond;
            }

            $dateStartString = date('j M y', strtotime($dateStart));
            $dateEndString = date('j M y', strtotime($dateEnd));
            $durationTotal = gmdate('H:i:s', $durationSecondTotal);
            $overtimeTotal = gmdate('H:i:s', $overtimeSecondTotal);

            if ($export == 'csv') {
                // Proses eksport CSV (harap disesuaikan sesuai kebutuhan)
            } else {
                return response()->json([
                    'status' => 'SUCCESS',
                    'interval' => $interval,
                    'date_start' => $dateStartString,
                    'date_end' => $dateEndString,
                    'id_artist' => $idUser,
                    'artist' => $user->name,
                    'report_duration_total' => $durationTotal,
                    'report_overtime_total' => $overtimeTotal,
                    'data' => $userDuration,
                ]);
            }
        } else {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
    }

}
