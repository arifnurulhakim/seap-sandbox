<?php

namespace App\Http\Controllers;

use App\Models\ArtistRating;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSession;
use App\Models\User;
use App\Models\WorkProject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $idTask = '')
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $cekuser = Users::where('id', $user->id)->first();
        $role_id = $cekuser->role_id;
        if (!$user || !in_array($role_id, [1, 2, 3, 5])) {
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

        $defaultOrder = "status_sort ASC, projectTask.name ASC";

        // Order mappings
        $orderMappings = [
            'projectDesc' => 'projectTask.name DESC',
            'projectAsc' => 'projectTask.name ASC',
            'clientDesc' => 'projectTask.client DESC',
            'clientAsc' => 'projectTask.client ASC',
            'jobDesc' => 'projectTask.job_desc DESC',
            'jobAsc' => 'projectTask.job_desc ASC',
            'contactDesc' => 'projectTask.contact_name DESC',
            'contactAsc' => 'projectTask.contact_name ASC',
            'countryDesc' => 'rc.name DESC',
            'countryAsc' => 'rc.name ASC',
            'leadDesc' => 'users.name DESC',
            'leadAsc' => 'users.name ASC',
            'statusDesc' => 'status_sort DESC, projectTask.name ASC',
            'statusAsc' => 'status_sort ASC, projectTask.name ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

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

        $filter = isset($filter) ? $filter : '';

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        $whereLead = '';
        if (!in_array($role_id, [1, 2, 3, 5])
        ) {
            $whereLead = " AND (projectTask.id_lead = '" . $user . "')";
        }

        $whereId = '';
        if ($idTask) {
            $whereId = " AND (projectTask.id = '" . $idTask . "')";
        }

        $whereSearch = '';
        if ($search) {
            $whereSearch = " AND (projectTask.name LIKE '%" . $search . "%' OR projectTask.client LIKE '%" . $search . "%' OR projectTask.contact_name LIKE '%" . $search . "%' OR users.name LIKE '%" . $search . "%')";
        }

        $whereStatus = '';
        if ($status) {
            $whereStatus = " AND projectTask.status = '" . $status . "'";
        }

    }

    public function getProjectTasks(Request $request, $idAsset = null)
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
        if (!$user || !in_array($role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $filter = $request->input('filter', '');

        $dateStart = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $request->input('date_start')))
        ? $request->input('date_start')
        : date('Y-m-d', strtotime($request->input('date_start')));

        $dateEnd = (preg_match('/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))$/', $request->input('date_end')))
        ? $request->input('date_end')
        : date('Y-m-d');

        $orderMappings = [
            'taskDesc' => 'project_task.name DESC',
            'taskAsc' => 'project_task.name ASC',
            'artistDesc' => 'up.name DESC',
            'artistAsc' => 'up.name ASC',
            'dateDesc' => 'project_task.date_task DESC',
            'dateAsc' => 'project_task.date_task ASC',
            'timeDesc' => 'project_task.time_estimate DESC',
            'timeAsc' => 'project_task.time_estimate ASC',
            'durationDesc' => 'project_task_session.duration DESC',
            'durationAsc' => 'project_task_session.duration ASC',
            'statusDesc' => 'project_task.status DESC',
            'statusAsc' => 'project_task.status ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $defaultOrder = ($filter == 'log') ? "work_project.id DESC" : "work_project.id ASC, work_project.name ASC";

        $getOrder = $request->input('order', '');
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $type = $request->get('type', '');

        $work_project = WorkProject::select('work_project.id as id',
            'project_category.id AS category_id',
            'work_project.name as name',
            'work_project.client',
            'users.name AS lead',
            'project_category.name AS category',
            'project_subasset.name AS asset',
            'project_subasset.detail AS detail',
        )
            ->leftJoin('project_category', 'project_category.id_project', '=', 'work_project.id')
            ->leftJoin('project_subasset', 'project_subasset.id_category', '=', 'project_category.id')
            ->leftJoin('users', 'users.id', '=', 'work_project.id_lead')
            ->where('project_subasset.id', $idAsset)
            ->first();
        // dd($work_project);

        $query = ProjectTask::select(
            'project_task.id',
            'project_task.name as task',
            'project_task.description',
            'project_task.id_subasset as id_asset',
            'project_task.id_artist',
            'users.name as artist',
            'project_task.date_task',
            \DB::raw("DATE_FORMAT(pis_project_task.date_task, '%e %b %Y') AS date_task_string"),
            'project_task.time_estimate',
            'project_task.status'
        )
            ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
            ->leftJoin('users', 'users.id', '=', 'project_task.id_artist')
            ->where('project_subasset.id', $idAsset)
            ->when($filter == 'log', function ($query) {
                $query->where(function ($query) {
                    $query->where('project_task.status', 'done')->orWhere('project_task.status', 'paid');
                });
            })
            ->distinct();
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $resultAll = $query->get();
        $result = $query->offset($offset)->limit($limit)->get();
        foreach ($result as $asset) {
            $duration = ProjectTaskSession::select(DB::raw("SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS duration"))
                ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->where('project_task.id', $asset->id)
                ->first();

            $artistlist = ProjectTaskSession::select('users.id as id_artist', 'users.name as artist')
                ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                ->where('project_task.id', $asset->id)->distinct()->orderby('artist', 'asc')->get();

            // dd($artistlist);

            foreach ($artistlist as $artistList) {

                $durationArtist = ProjectTaskSession::select(DB::raw("SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS duration"))
                    ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                    ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                    ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->where('project_task.id', $asset->id)
                    ->where('project_task_session.id_artist', $artistList->id_artist)
                    ->first();

                $score = ArtistRating::select('id_artist', 'id_task')
                    ->selectRaw("GROUP_CONCAT(category, ':', score) AS all_score")
                    ->selectRaw("SUM(CASE WHEN category = 'duration' THEN score ELSE 0 END) AS duration_score")
                    ->selectRaw("SUM(CASE WHEN category = 'spe' THEN score ELSE 0 END) AS spe_score")
                    ->selectRaw("SUM(CASE WHEN category = 'qua' THEN score ELSE 0 END) AS qua_score")
                    ->selectRaw("SUM(CASE WHEN category = 'com' THEN score ELSE 0 END) AS com_score")
                    ->where('id_task', $asset->id)
                    ->where('id_artist', $artistList->id_artist)
                    ->where('id_lead', $user->id)
                    ->groupBy('id_artist', 'id_task')
                    ->first();

                $hours = floor($durationArtist->duration / 3600);
                $minutes = floor(($durationArtist->duration % 3600) / 60);
                $seconds = $durationArtist->duration % 60;
                $timeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                $artistList->duration = $durationArtist ? $timeFormatted : '00:00:00';
                $artistList->all_score = $score ? $score->all_score : '0';
                $artistList->spe_score = $score ? $score->spe_score : '0';
                $artistList->qua_score = $score ? $score->qua_score : '0';
                $artistList->com_score = $score ? $score->com_score : '0';

            }
            $hours = floor($duration->duration / 3600);
            $minutes = floor(($duration->duration % 3600) / 60);
            $seconds = $duration->duration % 60;
            $timeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            $asset->duration = $duration ? $timeFormatted : '00:00:00';
            $asset->artist_list = $artistlist;

        }

        return response()->json(['status' => 'SUCCESS', 'data_project' => $work_project, 'offset' => $offset, 'limit' => $limit, 'data_total' => count($resultAll), 'data' => $result]);

    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_task' => 'integer',
            'id_asset' => 'required|numeric',
            // 'id_subasset' => 'required|numeric',
            'id_artist' => 'required|numeric',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'time_start' => 'nullable|date_format:Y-m-d H:i:s',
            // 'time_end' => 'nullable|date_format:Y-m-d H:i:s|after:time_start',
            'time_estimate' => 'nullable',
            'date_task' => 'nullable|date',
            'status' => 'required|string',
            // 'log_user' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($request->has('id_task')) {
            $projectTask = ProjectTask::findOrFail($request->id_task);
            $projectTask->update([
                'id_subasset' => $request->input('id_asset'),
                'id_artist' => $request->input('id_artist'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'time_estimate' => $request->input('time_estimate'),
                'date_task' => $request->input('date_task'),
                'status' => $request->input('status'),
            ]);
        } else {
            $projectTask = ProjectTask::create([
                'id_subasset' => $request->input('id_asset'),
                'id_artist' => $request->input('id_artist'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'time_estimate' => $request->input('time_estimate'),
                'date_task' => $request->input('date_task'),
                'status' => $request->input('status'),
                'log_time' => now(),
            ]);
            $work_project = ProjectTask::select(
                'project_task.id',
                'work_project.id as project_id',
                'work_project.name as work_project')
                ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
                ->where('project_subasset.id', $request->input('id_asset'))->first();
            // dd($work_project);

            $message = "Task baru: " . $request->input('name') . " - WorkProject: " . $work_project->work_project;
            // $link = "project_task_lead.html/" . $projectTask->id_asset . "/" . $projectTask->id_project;
            $link_default = '';
            $link_url_default = '';
            if ($work_project) {
                $link_default = $request->link ? $request->link : 'notifications';
                $link_url_default = $request->link_url ? $request->link_url : ' https://doddi.plexustechdev.com/fe-oraylog/notifications';
            } else {
                $link_default = $request->link ? $request->link : 'notifications';
                $link_url_default = $request->link_url ? $request->link_url : ' https://doddi.plexustechdev.com/fe-oraylog/notifications';
            }

        }

        return response()->json([
            'status' => 'success',
            'data' => $projectTask,
            'message' => 'ProjectTask ' . ($request->has('id_project') ? 'updated' : 'created') . ' successfully',
        ], Response::HTTP_CREATED);
    }

    // public function destroy(ProjectTask $projectTask)
    // {
    //     $projectTask->delete();

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'ProjectTask deleted successfully',
    //     ]);
    // }
    public function destroy(ProjectTask $projectTask)
    {
        try {
            if ($projectTask) {
                $projectTask->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'WorkProject task deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'WorkProject task not found',
                    'error_code' => 'PROJECT_TASK_NOT_FOUND',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    public function projectTaskSession(Request $request, $idUser, $idTask)
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
        if (!$user || !in_array($role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $result = DB::table('project_task_session')
            ->select('id', 'time_start', 'time_end',
                DB::raw("IF(time_start != '0000-00-00 00:00:00', DATE_FORMAT(time_start, '%e %b %Y - %H:%i:%s'), 'progress') AS time_start_string"),
                DB::raw("IF(time_end != '0000-00-00 00:00:00', DATE_FORMAT(time_end, '%e %b %Y - %H:%i:%s'), 'progress') AS time_end_string")
            )
            ->where('id_artist', $idUser)
            ->where('id_task', $idTask)
            ->orderBy('time_start', 'DESC')
            ->get();

        return response()->json(['status' => 'SUCCESS', 'data' => $result]);

    }
    public function UpdateprojectTaskSession(Request $request, $idSession)
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
        if (!$user || !in_array($role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'time_start' => 'string',
            'time_end' => 'string',
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $projectTaskSession = ProjectTaskSession::find($idSession);

        if (!$projectTaskSession) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'WorkProject Task Session not found',
                'error_code' => 'not_found',
            ], 404);
        }

        $projectTaskSession->update($request->all());

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'WorkProject Task Session updated successfully',
            'data' => $projectTaskSession,
        ]);

        return response()->json(['status' => 'SUCCESS', 'data' => $result]);

    }

}
