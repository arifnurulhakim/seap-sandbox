<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\ArtistRating;
use App\Models\ProjectCategory;
use App\Models\ProjectSubasset;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSession;
use App\Models\User;
use App\Models\UserIp; // Assuming you have a model for the work_project task session
use App\Models\WorkProject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

// use App\Services\NotificationService;
date_default_timezone_set('Asia/Jakarta');
class ProjectTaskArtistController extends Controller
{
    // protected $notificationService;

    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }

    public function indexArtist(Request $request, $filter = '')
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $offset = request()->get('offset', 0);
        $limit = request()->get('limit', 10);
        $artist = request()->get('artist', '');
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $getOrder = request()->get('order', '');
        $defaultOrder = ($filter == 'log') ? "pis_project_task.date_task DESC, pis_project_task.time_estimate ASC" : "pis_project_task.date_task ASC, pis_project_task.time_estimate ASC";

        // Order mappings
        $orderMappings = [
            'taskDesc' => 'pis_project_task.name DESC',
            'taskAsc' => 'pis_project_task.name ASC',
            'projectDesc' => 'work_project.name DESC',
            'projectAsc' => 'work_project.name ASC',
            'categoryDesc' => 'project_category.name DESC',
            'categoryAsc' => 'project_category.name ASC',
            'dateDesc' => 'pis_project_task.date_task DESC',
            'dateAsc' => 'pis_project_task.date_task ASC',
            'timeDesc' => 'pis_project_task.time_estimate DESC',
            'timeAsc' => 'pis_project_task.time_estimate ASC',
            'durationDesc' => 'IF(pis_project_task_session_daily.duration, TIME_FORMAT(pis_project_task_session_daily.duration, "%H:%i:%s"), "00:00:00") DESC',
            'durationAsc' => 'IF(pis_project_task_session_daily.duration, TIME_FORMAT(pis_project_task_session_daily.duration, "%H:%i:%s"), "00:00:00") ASC',
            'statusDesc' => 'pis_project_task.status DESC',
            'statusAsc' => 'pis_project_task.status ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        // Validation rules for input parameters
        $validOrderValues = implode(',', array_keys($orderMappings));
        $rules = [
            'offset' => 'integer|min:0',
            'limit' => 'integer|min:1',
            'artist' => 'string',
            'order' => "in:$validOrderValues",
        ];

        $validator = Validator::make([
            'offset' => $offset,
            'limit' => $limit,
            'artist' => $artist,
            'order' => $getOrder,
        ], $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
            ], 400);
        }
        // $hasProjectManagerRole = true; // Assuming we don't have the token but still want to include work_project managers
        // $artist = isset($artist) ? $artist : ''; // Set $artist to an empty string if it's not defined

        // // Define $whereArtist based on the condition
        // $whereArtist = '';
        // if ($hasProjectManagerRole && $artist) {
        //     $whereArtist = " AND user.username = '{$artist}'";
        // } else {
        //     // Assuming there's a function to get the current user
        //     $user = getCurrentUser();
        //     $whereArtist = " AND pis_project_task.id_artist = '{$user->id}'";
        // }

        // // Define $whereStatus based on the $filter condition
        $filter = isset($filter) ? $filter : '';

        $query = ProjectTask::select(
            'project_task.id',
            'work_project.id as project_id',
            'work_project.name as work_project',
            'lead.name as lead_artist',
            'project_category.name as category',
            'project_subasset.name as asset',
            'project_task.description',
            'project_task.date_task',
            \DB::raw("DATE_FORMAT(pis_project_task.date_task, '%e %b %Y') AS date_task_string"), // Fixed: Use 'project_task.date_task' instead of 'pis_project_task.date_task'
            'project_task.time_estimate',
            'project_task.status',
            \DB::raw('IFNULL(pis_project_ips.is_ip, 0) as is_ip')
        )
            ->leftJoin('users', 'users.id', '=', 'project_task.id_artist')
            ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
            ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
            ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
            ->leftJoin('project_ips', 'project_ips.project_id', '=', 'work_project.id')
            ->leftJoin('users as lead', 'lead.id', '=', 'work_project.id_lead') // Fixed: Aliased 'users' as 'lead' and corrected the alias usage
            ->where('project_task.id_artist', $user->id);

        if ($filter == 'log') {
            $query->where(function ($query) {
                $query->where('project_task.status', 'done')->orWhere('project_task.status', 'paid');
            });
        } else {
            $query->where(function ($query) {
                $query->where('project_task.status', '!=', 'done')->where('project_task.status', '!=', 'paid');
            });
        }

        $query->distinct();

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $resultAll = $query->get();
        $result = $query->offset($offset)->limit($limit)->get();

        foreach ($result as $task) {
            $today = now();
            $duration = ProjectTaskSession::select(
                // DB::raw("SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS duration"),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, pis_project_task_session.time_start, IF(pis_project_task_session.time_end = "0000-00-00 00:00:00", "' . $today . '", pis_project_task_session.time_end))) as duration_sec'),
                DB::raw("IF(pis_project_task_session.time_start, IF(pis_project_task_session.time_end, 'play', 'pause'), 'play') AS button"),
                DB::raw("IF(pis_project_task_session.time_end, '0', pis_project_task_session.is_overtime) AS is_overtime"),
                DB::raw("MAX(pis_project_task_session.id) AS id_session_max"),
                'project_task_session.time_start AS time_start_debug',
                'project_task_session.time_end AS time_end_debug'
            )
                ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->where('project_task.id', $task->id)
                ->first();
            // dd($duration);
            $hours = floor($duration->duration_sec / 3600);
            $minutes = floor(($duration->duration_sec % 3600) / 60);
            $seconds = $duration->duration_sec % 60;
            $timeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            $duration->duration = $timeFormatted;

            $button = ProjectTaskSession::select(DB::raw("IF(pis_project_task_session.time_start, IF(pis_project_task_session.time_end, 'play', 'pause'), 'play') AS button"), 'is_ip_session')->where('project_task_session.id', $duration->id_session_max)->first();
            // dd($button);
            $artistlist = ProjectTaskSession::select('users.id as id_artist', 'users.name as artist')
                ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                ->where('project_task.id', $task->id)->distinct()->orderby('artist', 'asc')->get();

            foreach ($artistlist as $artistList) {

                $durationArtist = ProjectTaskSession::select(DB::raw("SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS duration"))
                    ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                    ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                    ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->where('project_task.id', $task->id)
                    ->where('project_task_session.id_artist', $artistList->id_artist)
                    ->first();
                $durationTotal = $durationArtist->duration ?? 0;

                // // Ubah total durasi ke format "00:00:00"
                // $durationTotalFormatted = gmdate('H:i:s', $durationTotal);

                // // Gunakan hasil yang sudah diformat
                // $durationTotalFormatted = $durationTotalFormatted ?: '00:00:00';

                $hours = floor($durationTotal / 3600);
                $minutes = floor(($durationTotal % 3600) / 60);
                $seconds = $durationTotal % 60;
                $timeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $artistList->duration = $timeFormatted;

// Format hasil ke H:i:s
            }
            $durationTotal = $duration->duration_sec ?? 0;

            // Ubah total durasi ke format "00:00:00"
            $durationTotalFormatted = gmdate('H:i:s', $durationTotal);

            // Gunakan hasil yang sudah diformat
            $durationTotalFormatted = $durationTotalFormatted ?: '00:00:00';

            $task->duration = $durationTotalFormatted;
            $task->duration_second = $duration ? $duration->duration_sec : '0';
            $task->button = $button ? $button->button : 'play-work_project';

            if ($button && $button->is_ip_session == 0) {
                $task->button .= '-work_project';
            } elseif ($button && $button->is_ip_session == 1) {
                $task->button .= '-ip';
            }
            $task->is_overtime = $duration ? $duration->is_overtime : '0';
            $task->id_session = $duration ? $duration->id_session_max : null;
            $task->time_start_debug = $duration ? $duration->time_start_debug : null;
            $task->time_end_debug = $duration ? $duration->time_end_debug : null;
            $task->artist_list = $artistlist;

        }
        if (count($result) === 0) {
            return response()->json(['status' => 'ERROR', 'message' => 'Task Not Found', 'error_code' => 'task_not_found']);
        } else {
            $today = now();

            $userTask = DB::table('project_task_session')
                ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND,  time_start,IF(time_end = "0000-00-00 00:00:00", "' . $today . '", time_end))) as total_duration'))
                ->where('id_artist', $user->id)
                ->whereDate('time_start', '>=', $today)
                ->first();
            $durationTotal = $userTask->total_duration ?? 0;

            // Ubah total durasi ke format "00:00:00"
            // $durationTotalFormatted = gmdate('H:i:s', $durationTotal);

            // Gunakan hasil yang sudah diformat

            $hours = floor($durationTotal / 3600);
            $minutes = floor(($durationTotal % 3600) / 60);
            $seconds = $durationTotal % 60;
            $timeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            $todayTasktimeFormatted = $timeFormatted ?: '00:00:00';
            // $durationTotalFormatted = $durationTotalFormatted ?: '00:00:00';

            // Extract the total duration from the result
            // $todayTask = $userTask->total_duration ?? '00:00:00';
            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'dataTotal' => count($resultAll),
                'today_task' => $todayTasktimeFormatted,
                'today_task_sec' => $userTask->total_duration,
                'data_total' => count($resultAll),
                'data' => $result,
            ]);
        }
    }
    public function storeTask(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $request->validate([
            'id_task' => 'sometimes|required|integer',
            'id_asset' => 'required|integer',
            'id_artist' => 'integer|nullable',
            'name' => 'required|string',
            'description' => 'string|nullable',
            'date_task' => 'required|date',
            'time_estimate' => 'required|string',
            'status' => 'string|in:waiting,revision,done,paid',
        ]);

        $user = Auth::user();
        $userId = $user->id;
        $postData = $request->post();
        $idTask = $postData['id_task'] ?? null;
        $idAsset = $postData['id_asset'];
        $idArtist = $postData['id_artist'] ?? null;
        $name = $postData['name'];
        $description = $postData['description'] ?? '';
        $dateTask = $postData['date_task'];
        $timeEstimate = $postData['time_estimate'];
        $status = $postData['status'] ?? 'waiting';
        $now = Carbon::now();

        $dataProject = ProjectSubasset::with('category.work_project')
            ->where('id', $idAsset)
            ->when(!$user->can(['super_admin', 'project_owner', 'project_manager']), function ($query) use ($user) {
                return $query->where('p.id_lead', $user->id);
            })
            ->first();

        if (!$dataProject) {
            return response()->json(['status' => 'ERROR', 'message' => 'WorkProject Asset Not Found', 'error_code' => 'project_asset_not_found']);
        }

        $dataTask = ProjectTask::where('id', $idTask)
            ->when(!in_array('super_admin', checkPermission($token)) && !in_array('project_owner', checkPermission($token)) && !in_array('project_manager', checkPermission($token)) && !in_array('lead_artist', checkPermission($token)), function ($query) use ($token) {
                return $query->where('t.id_artist', checkUser($token));
            })
            ->first();

        if ($idTask && !$dataTask) {
            return response()->json(['status' => 'ERROR', 'message' => 'WorkProject Task Not Found', 'error_code' => 'project_task_not_found']);
        }

        if ($idTask) {
            // Update WorkProject Task
            $updateArtist = $idArtist ? $idArtist : null;
            $dataTask->update([
                'id_subasset' => $idAsset,
                'id_artist' => $updateArtist,
                'name' => $name,
                'description' => $description,
                'time_estimate' => $timeEstimate,
                'date_task' => $dateTask,
                'status' => $status,
            ]);

            // Remaining code for handling status and sending notifications (omitted for brevity)...

            $dataTask->save();

            return response()->json(['status' => 'SUCCESS', 'id_task' => $idTask, 'notification' => $notification]);
        } else {
            // New WorkProject Task
            $insertArtist = $idArtist ? $idArtist : null;
            $newTask = new ProjectTask([
                'id_subasset' => $idAsset,
                'id_artist' => $insertArtist,
                'name' => $name,
                'description' => $description,
                'time_estimate' => $timeEstimate,
                'date_task' => $dateTask,
                'status' => $status,
            ]);

            $newTask->save();

            // Remaining code for sending notifications (omitted for brevity)...

            return response()->json(['status' => 'SUCCESS', 'id_task' => $newTask->id, 'notification' => $notification]);
        }
    }
    public function storeArtist(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $userId = $user->id;
        $idSession = $request->input('id_session', null);
        $idTask = $request->input('id_task');
        $isOvertime = $request->input('is_overtime', false);
        $isIpSession = $request->input('is_ip_session', false);
        $now = now();

        // Validation rules for input parameters
        $rules = [
            'id_session' => 'nullable|integer',
            'id_task' => 'required|integer',
            'is_overtime' => 'boolean',
            'is_ip_session' => 'boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
            ], 400);
        }
        $cektaskSession = '';
        if ($idSession) {
            $cektaskSession = ProjectTaskSession::where('id', $idSession)->first();
        }

        if ($isIpSession == 0 && $cektaskSession == null) {
            $checkAttendance = Absensi::select('id', 'start_day')->where('user_id', $user->id)->where('end_day', null)->first();

            if (!$checkAttendance) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'you not attendance',
                    'error_code' => 'NOT_ATTENDANCE',
                ], 403);
            }
        }

        // Check if work_project task exists and not done
        $projectTask = DB::table('project_task')
            ->select('id', 'name as task')
            ->where('id', $idTask)
            ->where('status', '!=', 'done')
            ->first();
        // dd($isIpSession);

        if ($isIpSession == 1) {
            $cekProject = ProjectTask::join('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->join('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->join('work_project', 'work_project.id', '=', 'project_category.id_project')
            // ->leftjoin('project_ips', 'project_ips.project_id', '=', 'work_project.id')
            // ->leftjoin('user_ips', 'user_ips.project_id', '=', 'work_project.id')
                ->where('project_task.id', $idTask)
                ->select('work_project.id')
            // ->where('user_ips.user_id', $user->id)
            // ->select('user_ips.user_id')
                ->first();
            // dd($cekProject);
            $isIpCheck = UserIp::leftjoin('work_project', 'work_project.id', '=', 'user_ips.project_id')->where('user_ips.user_id', $userId)->where('work_project.id', $cekProject->id)->first();
            // dd($isIpCheck);
            if (!$isIpCheck) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'you not colaborator',
                    'error_code' => 'NOT_COLABOLATOR',
                ], 403);
            }
        }

        if (!$projectTask) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'WorkProject Task Not Found or Already Completed',
                'error_code' => 'project_task_not_found_or_completed',
            ], 404);
        }

        if ($idSession) {
            // Update WorkProject Task Session - End Session
            DB::table('project_task_session')
                ->where('id', $idSession)
                ->update(['time_end' => $now]);
        } else {
            // New WorkProject Task Session - Start Session
            $timeEndPlaceholder = Carbon::createFromFormat('Y-m-d H:i:s', '0000-00-00 00:00:00');

            $idSession = DB::table('project_task_session')->insertGetId([
                'id_task' => $idTask,
                'id_artist' => $userId,
                'time_start' => $now,
                'time_end' => "0000-00-00 00:00:00",
                // 'time_end' =>$timeEndPlaceholder,
                'is_overtime' => $isOvertime,
                'is_overtime_backup' => $isOvertime,
                'is_ip_session' => $isIpSession,
                'correction' => '',
            ]);

            // Update Task - status = progress
            DB::table('project_task')
                ->where('id', $idTask)
                ->where('status', '!=', 'done')
                ->update(['status' => 'progress']);

            // Update Asset - status = progress
            $assetId = DB::table('project_task')
                ->leftJoin('project_subasset', 'project_task.id_subasset', '=', 'project_subasset.id')
                ->where('project_task.id', $idTask)
                ->value('project_subasset.id');

            if ($assetId) {
                DB::table('project_subasset')
                    ->where('id', $assetId)
                    ->where('status', '!=', 'done')
                    ->update(['status' => 'progress']);
            }

            // Update WorkProject - status = progress
            $projectId = DB::table('project_task')
                ->leftJoin('project_subasset', 'project_task.id_subasset', '=', 'project_subasset.id')
                ->leftJoin('project_category', 'project_subasset.id_category', '=', 'project_category.id')
                ->where('project_task.id', $idTask)
                ->value('project_category.id_project');

            if ($projectId) {
                DB::table('work_project')
                    ->where('id', $projectId)
                    ->where('status', '!=', 'cancel')
                    ->where('status', '!=', 'done')
                    ->update(['status' => 'progress']);
            }
        }

        return response()->json([
            'status' => 'SUCCESS',
            'id_task' => $idTask,
            'id_session' => $idSession,
        ]);
    }
    public function requestReviewArtist(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $idTask = $request->get('id_task');

        // Validation rules for input parameters
        $rules = [
            'id_task' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Invalid input parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Check if task exists and retrieve the work_project details
        $projectTask = DB::table('project_task')
            ->select('project_task.id', 'project_task.name AS task', 'project_subasset.id AS id_asset', 'project_subasset.name AS asset', 'project_category.id AS id_category', 'project_category.name AS category', 'work_project.id AS id_project', 'work_project.name AS work_project', 'work_project.id_lead')
            ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
            ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
            ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
            ->where('project_task.id', $idTask)
            ->first();

        if (!$projectTask) {
            return response()->json(['status' => 'ERROR', 'message' => 'WorkProject Task Not Found', 'error_code' => 'project_task_not_found']);
        }

        // Update Task - status = review
        DB::table('project_task')
            ->where('id', $idTask)
            ->where('status', 'progress')
            ->update(['status' => 'review']);

        // Send Notification to Lead Artist
        $id_task = $projectTask->id_asset;
        $id_project = $projectTask->id_project;
        $message = "Task berikut menunggu untuk direview: " . $projectTask->task;
        // $link = "project_task_lead.html/" . $projectTask->id_asset . "/" . $projectTask->id_project;
        $link_default = '';
        $link_url_default = '';
        if ($id_project) {
            $link_default = $request->link ? $request->link . '/' . $id_task : 'work_project/task/' . $id_task;
            $link_url_default = $request->link_url ? $request->link_url . '/' . $id_task : ' https://doddi.plexustechdev.com/fe-oraylog/work_project/task/' . $id_task;
        } else {
            $link_default = $request->link ? $request->link : 'work_project/task';
            $link_url_default = $request->link_url ? $request->link_url : ' https://doddi.plexustechdev.com/fe-oraylog/work_project/task';
        }

        return response()->json(['status' => 'SUCCESS', 'id_task' => $idTask]);
    }
    public function getCurrentTasks()
    {
        $token = request()->header('token');
        $user = Auth::user();
        $getuser = User::where('id', $user->id)->first();
        if ($getuser->role_id != 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'role harus WorkProject Manager',
                'error_code' => 'ACCESS_FORBIDDEN',
            ], 401);
        }
        $offset = request()->get('offset', 0);
        $limit = request()->get('limit', 10);
        $order = request()->get('order', '');

        // Define the valid order options and corresponding order mappings
        $validOrderOptions = [
            'currentDesc' => 'pis_project_task_session.time_end DESC',
            'currentAsc' => 'pis_project_task_session.time_end ASC',
            'nameDesc' => 'pis_users.name DESC',
            'nameAsc' => 'pis_users.name ASC',
        ];

        // Set the default order if the provided order option is invalid
        $orderBy = $validOrderOptions[$order] ?? 'pis_users.name ASC, pis_project_task_session.time_end ASC';

        // Rest of the code
        // ...

        // Laravel database query using Query Builder
        $query = DB::table('project_task_session')
            ->select('project_task_session.id_artist AS user_id', 'users.name', 'project_task_session.time_end', 'work_project.name AS work_project', 'project_task.name AS task')
            ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
            ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
            ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
            ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
            ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
            ->whereNotIn('project_task_session.id_artist', function ($query) {
                $query->select('id')->from('user_out');
            })
            ->orderByRaw($orderBy);

        $result = $query->offset($offset)->limit($limit)->get();
        $resultAll = $query->get();

        if ($result->isEmpty()) {
            return response()->json(['status' => 'ERROR', 'message' => 'Task Not Found', 'error_code' => 'task_not_found']);
        } else {
            $data = $result->toArray();

            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'data_total' => count($resultAll),
                'data' => $data,
            ]);
        }
    }
    public function artistTaskOvertime(Request $request, $idSession)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated and has the necessary permissions (super_admin or admin)
        $cekuser = User::where('id', $user->id)->first();
        $role_id = $cekuser->role_id;

        if (!$user || !in_array($role_id, [1, 3, 5])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        // Validate the presence of idSession parameter
        if (!$idSession) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Empty Field',
                'error_code' => 'empty_field',
            ], 400);
        }

        // Get the is_overtime parameter from the request body
        $isOvertime = $request->input('is_overtime');

        // Update Session
        $affectedRows = DB::table('project_task_session')
            ->where('id', $idSession)
            ->update(['is_overtime' => $isOvertime]);

        if ($affectedRows > 0) {
            // $logMessage = ($isOvertime == '1') ? 'set as overtime' : 'set as not overtime';
            // logActivity($user->id, $logMessage, json_encode(['id_session' => $idSession]), $idSession, 'id_session');
            $logMessage = '';
            if ($isOvertime == 1) {
                $logMessage = "overtime";
            } else {
                $logMessage = "not overtime";
            }
            return response()->json([
                'status' => 'SUCCESS',
                'id_session' => $idSession,
                'is_overtime' => $isOvertime,
                'overtime status' => $logMessage,
            ], 200);
        } else {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Session Not Found',
                'error_code' => 'session_not_found',
            ], 404);
        }
    }
    public function artistTaskReport2(Request $request, $user_id = '')
    {
        // Get the parameters from the request query string
        $dateStart = $request->input('date_start', date('Y-m-d'));
        $dateEnd = $request->input('date_end', date('Y-m-d'));

        // Get the authenticated user
        $user = Auth::user();
        $userRole = User::where('id', $user->id)->first();
        $role_id = $userRole->role_id;
        $getuserid = $user->id;

        // Check if the user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        if (!in_array($role_id, [1, 3, 5])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        // Perform your logic to calculate the task durations and overtime for the date range
        $interval = abs((strtotime($dateStart) - strtotime($dateEnd)) / (60 * 60 * 24));
        $userDuration = [];
        $durationSecondTotal = 0;
        $overtimeSecondTotal = 0;

        for ($i = 0; $i <= $interval; $i++) {
            $date = date('Y-m-d', strtotime($dateStart . ' +' . $i . ' day'));
            $dateString = date('j F Y', strtotime($date));

            // Get the user's task for the current date
            $task = DB::table('project_task_session')
                ->select('id', 'id_task', 'id_artist',
                    DB::raw("IF(DATE_FORMAT(time_start, '%Y-%m-%d') < '{$dateStart}', '{$dateStart} 00:00:00', time_start) AS time_start"),
                    DB::raw("IF(DATE_FORMAT(time_end, '%Y-%m-%d') > '{$dateEnd}', '{$dateEnd} 23:59:59', time_end) AS time_end"),
                    'is_overtime'
                )
                ->where(function ($query) use ($dateStart, $dateEnd) {
                    $query->whereBetween('time_start', [$dateStart, $dateEnd])
                        ->orWhereBetween('time_end', [$dateStart, $dateEnd]);
                })
                ->where('time_end', '<>', '0000-00-00 00:00:00')
                ->where('id_artist', $user_id)
                ->get();

            $session = [];
            $overtimeSecond = 0;
            foreach ($task as $t) {
                $sessionDuration = strtotime($t->time_end) - strtotime($t->time_start);
                $isOvertime = ($t->is_overtime == '1') ? true : false;

                $session[] = [
                    'id' => $t->id,
                    'id_task' => $t->id_task,
                    'id_artist' => $t->id_artist,
                    'time_start' => $t->time_start,
                    'time_end' => $t->time_end,
                    'time_start_string' => date('j M y H:i:s', strtotime($t->time_start)),
                    'time_end_string' => date('j M y H:i:s', strtotime($t->time_end)),
                    'duration' => sprintf('%02d:%02d:%02d', floor($sessionDuration / 3600), floor($sessionDuration / 60 % 60), floor($sessionDuration % 60)),
                    'is_overtime' => $isOvertime,
                ];

                if ($isOvertime) {
                    $overtimeSecond += $sessionDuration;
                }
            }

            $duration = (count($session) > 0) ? $session[0]['duration'] : '00:00:00';
            $isOvertime = (count($session) > 0) ? $session[0]['is_overtime'] : '1';

            $userDuration[] = [
                'report_date_string' => $dateString,
                'report_date' => $date,
                'report_duration' => $duration,
                'report_duration_session' => $session,
                'report_overtime' => sprintf('%02d:%02d:%02d', floor($overtimeSecond / 3600), floor($overtimeSecond / 60 % 60), floor($overtimeSecond % 60)),
                'report_overtime_valid' => $isOvertime,
            ];

            sscanf($duration, "%d:%d:%d", $hours, $minutes, $seconds);
            $durationSecond = ($hours * 3600) + ($minutes * 60) + $seconds;

            $durationSecondTotal += $durationSecond;
            $overtimeSecondTotal += $overtimeSecond;
        }

        $dateStartString = date('j M y', strtotime($dateStart));
        $dateEndString = date('j M y', strtotime($dateEnd));
        $durationTotal = sprintf('%02d:%02d:%02d', floor($durationSecondTotal / 3600), floor($durationSecondTotal / 60 % 60), floor($durationSecondTotal % 60));
        $overtimeTotal = sprintf('%02d:%02d:%02d', floor($overtimeSecondTotal / 3600), floor($overtimeSecondTotal / 60 % 60), floor($overtimeSecondTotal % 60));

        // Return the report data in JSON format
        return response()->json([
            'status' => 'SUCCESS',
            'interval' => count($userDuration),
            'date_start' => $dateStartString,
            'date_end' => $dateEndString,
            'user_id' => $user_id,
            'report_duration_total' => $durationTotal,
            'report_overtime_total' => $overtimeTotal,
            'data' => $userDuration,
        ]);
    }
    public function artistTaskReport(Request $request)
    {
        // Get the parameters from the request query string
        $dateStart = $request->filled('date_start')
        ? date('Y-m-d 00:00:01', strtotime($request->input('date_start')))
        : date('Y-m-d 00:00:01');
        $dateEnd = $request->filled('date_end')
        ? date('Y-m-d 23:59:59', strtotime($request->input('date_end')))
        : date('Y-m-d 23:59:59');

        // Get the authenticated user

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $userRole = User::where('id', $user->id)->first();
        $role_id = $userRole->role_id;
        $idUser = $user->id;
        if (in_array($role_id, [1, 3, 5])) {
            if ($request->user_id) {
                $idUser = $request->user_id;
                // dd($idUser);
                $user = User::where('id', $idUser)->first();
                if (!$user) {
                    return response()->json(['status' => 'ERROR', 'message' => 'User Not Found', 'error_code' => 'user_not_found']);
                }
            } else {
                $user_exclude = DB::table('user_exclude')
                    ->pluck('user_id')
                    ->toArray();
                $absensi = User::select('users.id', 'users.name')
                    ->whereNotIn('users.id', $user_exclude)
                    ->groupBy('users.id')
                    ->orderBy('users.name', 'asc');

                // dd(now());

                // dd(now()->toDateString());
                $resultAll = $absensi->get();
                $result = $absensi->get();
                foreach ($result as $stat) {
                    $totalDuration = ProjectTaskSession::where('id_artist', $stat->id)
                        ->where('time_start', '>=', $dateStart)
                        ->where('time_end', '<=', $dateEnd)
                        ->select(DB::raw('SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, time_start, IF(time_end = "0000-00-00 00:00:00", NOW(), time_end)))) as total_duration'))
                        ->first();

                    // Ambil total durasi dari hasil kueri
                    $duration = $totalDuration->total_duration;

                    // Tambahkan total durasi ke objek $stat
                    $stat->duration = $duration ? $duration : '00:00:00';
                }

                return response()->json([
                    'status' => 'SUCCESS',
                    'data' => $result,
                ]);

            }

        }
        $user = User::where('id', $idUser)->first();
        $dateStartString = date('j M y', strtotime($dateStart));
        $dateEndString = date('j M y', strtotime($dateEnd));
        $export = $request->export ?? ''; // Menggunakan null coalescing operator untuk menangani nilai null pada $request->export
        $exportType = ($export == 'csv' && in_array($role_id, [1, 2, 5])) ? 'csv' : ''; // Menggunakan operator ternary untuk
        $date_Start = Carbon::parse($request->input('date_start'));
        $date_End = Carbon::parse($request->input('date_end'));
        $interval = $date_End->diffInDays($date_Start);

        $profile = User::where('id', $idUser)->first();
        $ArtistProfile = User::where('id', $idUser)->first();
        $today = now();
        $totalTaskPerDay = ProjectTaskSession::where('id_artist', $idUser)
            ->where('time_start', '>=', $dateStart)
            ->where('time_end', '<=', $dateEnd)
            ->select(DB::raw('DATE(time_start) as task_date'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, time_start, IF(time_end = "0000-00-00 00:00:00", "' . $today . '", time_end))) as total_time_sec'))
            ->groupBy('task_date')
            ->get();

        $total = ProjectTaskSession::where('id_artist', $idUser)
            ->where('time_start', '>=', $dateStart)
            ->where('time_end', '<=', $dateEnd)
            ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND,  time_start,IF(time_end = "0000-00-00 00:00:00", "' . $today . '", time_end))) as total_duration'))
            ->first();

        $totalovr = ProjectTaskSession::where('id_artist', $idUser)
            ->where('time_start', '>=', $dateStart)
            ->where('time_end', '<=', $dateEnd)
            ->where('is_overtime', 1)
            ->select(DB::raw('SUM(TIMESTAMPDIFF(SECOND,  time_start,IF(time_end = "0000-00-00 00:00:00", "' . $today . '", time_end))) as total_duration'))
            ->first();

        // Ambil total durasi dari hasil kueri, tambahkan null coalescing untuk penanganan NULL
        $durationTotal = $total->total_duration ?? 0;

        $hours = floor($durationTotal / 3600);
        $minutes = floor(($durationTotal % 3600) / 60);
        $seconds = $durationTotal % 60;
        $durationTotalFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        $durationTotalFormatted = $durationTotalFormatted ?: '00:00:00';

        $overtimeTotal = $totalovr->total_duration ?? 0;
        $hours = floor($overtimeTotal / 3600);
        $minutes = floor(($overtimeTotal % 3600) / 60);
        $seconds = $overtimeTotal % 60;
        $overtimeTotalFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        $overtimeTotalFormatted = $overtimeTotalFormatted ?: '00:00:00';
        $overtimeSecond = 1;
        $data = [];
        for ($i = 0; $i <= $interval; $i++) {
            $report_date = date('Y-m-d', strtotime("+$i day", strtotime($dateStart)));
            $report_date_string = date('j F Y', strtotime("+$i day", strtotime($dateStart)));
            $totalTime = null;
            $totalTimeSec = null;
            // Cari apakah ada data kehadiran untuk tanggal tersebut dalam $totalTaskPerDay
            $overtimeSecondTotal = 0;
            foreach ($totalTaskPerDay as $taskArtist) {
                if ($taskArtist->task_date === $report_date) {
                    // Jika ada data, format waktu dan simpan ke dalam $totalTime
                    $totalTimeSec = $taskArtist->total_time_sec;
                    $hours = floor($taskArtist->total_time_sec / 3600);
                    $minutes = floor(($taskArtist->total_time_sec % 3600) / 60);
                    $seconds = $taskArtist->total_time_sec % 60;
                    $totalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    break;
                }
                $overtimeSecond = $taskArtist->is_overtime == '1' ? $sessionDuration : 0;

                $overtimeSecondTotal += $overtimeSecond;
            }

            // Set nilai report_start dan report_end ke null
            $report_start = '00:00:00';
            $report_end = '00:00:00';
            $report_interval = '0:0:0';
            $interval_seconds = 0;
            // Cari waktu start dan end yang paling awal dan paling akhir dalam satu hari
            $sessions = ProjectTaskSession::where('id_artist', $idUser)
                ->whereDate('time_start', $report_date)
                ->orderBy('time_start')
                ->get();
            foreach ($sessions as $session) {
                if ($session->time_end == '0000-00-00 00:00:00') {
                    $session->time_end = now()->toDateTimeString();
                }
            }

            if ($sessions->isNotEmpty()) {
                $report_start = date('H:i:s', strtotime($sessions->first()->time_start));
                $report_end = date('H:i:s', strtotime($sessions->last()->time_end));
                $interval_seconds = (strtotime($sessions->last()->time_end)) - (strtotime($sessions->first()->time_start));
                $hours = floor($interval_seconds / 3600);
                $minutes = floor(($interval_seconds % 3600) / 60);
                $seconds = $interval_seconds % 60;
                $report_interval = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
            $break = $interval_seconds - $totalTimeSec;
            if ($break) {

                $hours = floor($break / 3600);
                $minutes = floor(($break % 3600) / 60);
                $seconds = $break % 60;
                $break = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            $report_duration_session = ProjectTaskSession::where('project_task_session.id_artist', $idUser)
                ->whereDate('project_task_session.time_start', $report_date)
            // ->where('project_task_session.time_end', $report_date . ' 23:59:59')
                ->selectRaw('
                pis_project_task_session.id as id_session,
                pis_project_task_session.time_start,
                CASE
                    WHEN pis_project_task_session.time_end = "0000-00-00 00:00:00" THEN "' . $today . '"
                    ELSE pis_project_task_session.time_end
                END AS time_end,
                pis_project_task_session.is_overtime,
                DATE_FORMAT(pis_project_task_session.time_start, "%e %b %y %T") AS time_start_string,
                DATE_FORMAT(pis_project_task_session.time_end, "%e %b %y %T") AS time_end_string,
                SEC_TO_TIME(TIMESTAMPDIFF(SECOND, pis_project_task_session.time_start,
                CASE
                    WHEN pis_project_task_session.time_end = "0000-00-00 00:00:00" THEN "' . $today . '"
                    ELSE pis_project_task_session.time_end
                END)) AS duration
            ')
                ->get();

            $user_session = [];
            $is_overtime = 1;
            foreach ($report_duration_session as $s) {
                $overtimeSecond = 0;
                $sessionDuration = strtotime($s->time_end) - strtotime($s->time_start);
                $overtimeSecond += $s->is_overtime == '1' ? $sessionDuration : 0;
                $s->time_start_string = date('j M y H:i:s', strtotime($s->time_start));
                $s->time_end_string = date('j M y H:i:s', strtotime($s->time_end));
                $s->duration = gmdate('H:i:s', $sessionDuration);

                $user_session[] = [

                    'id_session' => $s->id_session,
                    'report_start' => $s->time_start,
                    'report_end' => $s->time_end,
                    'is_overtime' => $s->is_overtime,
                    'report_start_string' => date("d M y H:i:s", strtotime($s->time_start)),
                    'report_end_string' => date("d M y H:i:s", strtotime($s->time_end)),
                    "duration" => $s->duration,

                ];

            }

            $data[] = (object) [
                'report_date_string' => $report_date_string,
                'report_date' => $report_date,
                'report_start' => $report_start,
                'report_end' => $report_end,
                'report_interval' => $report_interval,
                // 'report_break' => $break,
                // 'report_break_sec' => $break->total_break_duration,
                'report_duration_sec' => $totalTimeSec ? $totalTimeSec : 0,
                'report_duration' => $totalTime ? $totalTime : '00:00:00',
                'report_duration_session' => $user_session,
                'report_overtime_valid' => $overtimeSecond,
                'report_overtime' => gmdate('H:i:s', $overtimeSecond),
                // 'report_notes' => $note ? $note->notes : '',
            ];
        }
        if ($exportType == 'csv') {
            $dateStartFormatted = Carbon::parse($dateStart)->format('Y-m-d');
            $dateEndFormatted = Carbon::parse($dateEnd)->format('Y-m-d');
            $filename = "oraylog_{$dateStartFormatted}_{$dateEndFormatted}_{$user->username}.csv";
            $filename_path = storage_path("public/media/TaskArtist/$filename");
            $filename_url = ("media/TaskArtist/$filename");

            // Data untuk dimasukkan ke dalam CSV
            $csv_data = [
                ['No', 'Date', 'Task(Real Time)', 'Overtime'],
            ];

            // Mengisi data CSV dari $data
            foreach ($data as $key => $dataItem) {
                $csv_data[] = [
                    $key + 1,
                    $dataItem->report_date_string,
                    $dataItem->report_duration,
                    $dataItem->report_overtime,
                ];
            }

            $directory = 'media/TaskArtist';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            // Menambahkan total durasi pada akhir CSV
            $csv_data[] = ['', 'TOTAL', $durationTotalFormatted, $overtimeTotalFormatted];

            // Buat string CSV
            $csv_content = '';
            foreach ($csv_data as $fields) {
                $csv_content .= implode(';', $fields) . PHP_EOL;
            }

            Storage::put("media/TaskArtist/$filename", $csv_content);

            // Mengembalikan respons JSON dengan informasi file CSV
            return response()->json([
                'status' => 'SUCCESS',
                'filename' => $filename,
                'filename_url' => env('STORAGE_URL') . $filename_url . '?rand=' . rand(1, 1000000),
                'csv_data' => $data,
            ]);
        }

        return response()->json(['status' => 'SUCCESS',
            'idUser' => $idUser,
            'username' => $profile->name,
            'interval' => $interval,
            'date_start' => $dateStartString,
            'date_end' => $dateEndString,
            'id_artist' => $idUser,
            'artist' => $ArtistProfile->name,
            'report_duration_total' => $durationTotalFormatted,
            'report_overtime_total' => $overtimeTotalFormatted,
            'data' => $data,
            //
        ]);
    }

    public function projectTaskStop(Request $request)
    {
        // Get the token from the request headers
        $token = $request->header('token');

        // Get the date_time parameter from the request
        $dateTime = $request->input('date_time');

        // Check if the user is authorized
        if (!in_array('super_admin', checkPermission($token)) && !in_array('admin', checkPermission($token))) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
                'token' => $token,
                'permission' => checkPermission($token),
            ], 403);
        }

        // Check if the date_time parameter is provided
        if (empty($dateTime)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Empty Field: date_time',
                'error_code' => 'empty_field_date_time',
            ], 422);
        }

        // Check if there are work_project task sessions with the provided date_time
        $projectTaskSessions = ProjectTaskSession::where('time_start', '<=', $dateTime)
            ->where('time_end', '0000-00-00 00:00:00')
            ->get();

        if ($projectTaskSessions->isEmpty()) {
            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'WorkProject Task Session Not Found',
            ]);
        }

        // Update the work_project task sessions with the provided date_time
        $updatedRows = ProjectTaskSession::where('time_start', '<=', $dateTime)
            ->where('time_end', '0000-00-00 00:00:00')
            ->update(['time_end' => $dateTime]);

        // Log activity for each work_project task session stopped
        foreach ($projectTaskSessions as $session) {
            $artist = User::where('id', $session->id_artist)->value('name');
            $task = ProjectTask::find($session->id_task);
            $subasset = ProjectSubasset::find($task->id_subasset);
            $category = ProjectCategory::find($subasset->id_category);
            $work_project = WorkProject::find($category->id_project);

            logActivity(
                checkUser($token),
                'force stop work_project task session',
                json_encode([
                    'id_artist' => $session->id_artist,
                    'artist' => $artist,
                    'id_project' => $work_project->id,
                    'project_name' => $work_project->name,
                    'id_category' => $category->id,
                    'category_name' => $category->name,
                    'id_asset' => $subasset->id,
                    'asset_name' => $subasset->name,
                    'id_task' => $task->id,
                    'task_name' => $task->name,
                    'id_session' => $session->id,
                ]),
                $session->id,
                'id_session'
            );
        }

        return response()->json([
            'status' => 'SUCCESS',
            'updated_rows' => $updatedRows,
        ]);
    }

    public function RatingArtist(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $cekuser = User::where('id', $user->id)->first();
        $role_id = $cekuser->role_id;
        if (!$user || !in_array($role_id, [3, 5])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_artist' => 'required|numeric',
            'id_task' => 'required|numeric',
            'score' => 'required|numeric',
            'category' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $rating = ArtistRating::where('id_artist', $request->id_artist)->where('id_task', $request->id_task)->where('category', $request->category)->where('id_lead', $user->id)->first();

        if ($rating) {

            $rating->update([
                'score' => $request->input('score'),
            ]);
        } else {
            $rating = ArtistRating::create([
                'id_artist' => $request->id_artist,
                'id_task' => $request->id_task,
                'category' => $request->category,
                'id_lead' => $user->id,
                'score' => $request->score,
            ]);
        }
        return response()->json([
            'status' => 'success',
            'data' => $rating,
            'message' => 'Rating Artist ' . ($request->id_artist && $request->id_task ? 'updated' : 'created') . ' successfully',
        ], Response::HTTP_CREATED);
    }

}
