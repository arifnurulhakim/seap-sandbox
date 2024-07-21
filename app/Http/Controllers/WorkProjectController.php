<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\ProjectIp;
use App\Models\User;
use App\Models\UserIp;
use App\Models\WorkProject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $idProject = '')
    {
        $users = Auth::user();
        if (!$users) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $cekuser = User::where('id', $users->id)->first();
        $role_id = $cekuser->role_id;
        if (!$users || !in_array($role_id, [1, 2, 3, 4, 5])) {
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

        $defaultOrder = "status_sort ASC, work_project.name ASC";

        // Order mappings
        $orderMappings = [
            'projectDesc' => 'work_project.name DESC',
            'projectAsc' => 'work_project.name ASC',
            'clientDesc' => 'work_project.client DESC',
            'clientAsc' => 'work_project.client ASC',
            'jobDesc' => 'work_project.job_desc DESC',
            'jobAsc' => 'work_project.job_desc ASC',
            'contactDesc' => 'work_project.contact_name DESC',
            'contactAsc' => 'work_project.contact_name ASC',
            'countryDesc' => 'rc.name DESC',
            'countryAsc' => 'rc.name ASC',
            'leadDesc' => 'users.name DESC',
            'leadAsc' => 'users.name ASC',
            'statusDesc' => 'status_sort DESC, work_project.name ASC',
            'statusAsc' => 'status_sort ASC, work_project.name ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        $validOrderValues = imppisde(',', array_keys($orderMappings));
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
            $whereLead = " AND (work_project.id_lead = '" . $users . "')";
        }

        $whereId = '';
        if ($idProject) {
            $whereId = " AND (work_project.id = '" . $idProject . "')";
        }

        $whereSearch = '';
        if ($search) {
            $whereSearch = " AND (work_project.name LIKE '%" . $search . "%' OR work_project.client LIKE '%" . $search . "%' OR work_project.contact_name LIKE '%" . $search . "%' OR users.name LIKE '%" . $search . "%')";
        }

        $whereStatus = '';
        if ($status) {
            $whereStatus = " AND work_project.status = '" . $status . "'";
        }

    }

    public function getProjects(Request $request, $idProject = null)
    {
        $users = Auth::user();
        if (!$users) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $cekuser = User::where('id', $users->id)->first();
        $role_id = $cekuser->role_id;
        if (!$users || !in_array($role_id, [1, 2, 3, 4, 5])) {
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

        // Order mappings
        $orderMappings = [
            'projectDesc' => 'work_project.name DESC',
            'projectAsc' => 'work_project.name ASC',
            'clientDesc' => 'work_project.client DESC',
            'clientAsc' => 'work_project.client ASC',
            'jobDesc' => 'work_project.job_desc DESC',
            'jobAsc' => 'work_project.job_desc ASC',
            'contactDesc' => 'work_project.contact_name DESC',
            'contactAsc' => 'work_project.contact_name ASC',
            'countryDesc' => 'country.name DESC',
            'countryAsc' => 'country.name ASC',
            'leadDesc' => 'users.name DESC',
            'leadAsc' => 'users.name ASC',
            'statusDesc' => 'status_sort DESC, work_project.name ASC',
            'statusAsc' => 'status_sort ASC, work_project.name ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $defaultOrder = ($filter == 'log') ? "pis_work_project.id DESC" : "pis_work_project.id ASC, pis_work_project.name ASC";

        $getOrder = $request->input('order', '');
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        $query = WorkProject::leftJoin('users', 'users.id', '=', 'work_project.id_lead')
            ->leftJoin('country', 'country.id', '=', 'work_project.id_country')
            ->leftJoin('project_ips', 'project_ips.project_id', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT pis_project_category.id_project,
                            SEC_TO_TIME(SUM(TIME_TO_SEC(pis_project_task.time_estimate))) AS time_estimate
                        FROM pis_project_task
                        LEFT JOIN pis_project_subasset ON pis_project_subasset.id = pis_project_task.id_subasset
                        LEFT JOIN pis_project_category ON pis_project_category.id = pis_project_subasset.id_category
                        GROUP BY pis_project_category.id_project) pis_project_task_estimate'), 'project_task_estimate.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT pis_project_category.id_project,
                            SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)))) AS duration
                        FROM pis_project_task_session
                        LEFT JOIN pis_project_task ON pis_project_task.id = pis_project_task_session.id_task
                        LEFT JOIN pis_project_subasset ON pis_project_subasset.id = pis_project_task.id_subasset
                        LEFT JOIN pis_project_category ON pis_project_category.id = pis_project_subasset.id_category
                        WHERE pis_project_task_session.time_end != "0000-00-00 00:00:00"
                        GROUP BY pis_project_category.id_project) pis_project_task_session_duration'), 'project_task_session_duration.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT COUNT(pis_project_task.id) AS task_total, pis_project_category.id_project
                        FROM pis_project_task
                        LEFT JOIN pis_project_subasset sa ON sa.id = pis_project_task.id_subasset
                        LEFT JOIN pis_project_category ON pis_project_category.id = sa.id_category
                        GROUP BY pis_project_category.id_project) pis_project_task_total'), 'project_task_total.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT COUNT(pis_project_task.id) AS task_done, pis_project_category.id_project
                        FROM pis_project_task
                        LEFT JOIN pis_project_subasset sa ON sa.id = pis_project_task.id_subasset
                        LEFT JOIN pis_project_category ON pis_project_category.id = sa.id_category
                        WHERE pis_project_task.status = "done" OR pis_project_task.status = "paid"
                        GROUP BY pis_project_category.id_project) pis_project_task_done'), 'project_task_done.id_project', '=', 'work_project.id')
            ->select(
                'work_project.id', 'work_project.name', 'work_project.client', 'work_project.job_desc', 'work_project.contact_name', 'work_project.contact_email', 'work_project.description', 'work_project.requirement', 'work_project.status', DB::raw('COALESCE(pis_project_ips.is_ip, 0) AS is_ip'),
                'country.id AS id_country', 'country.name AS country', 'users.id AS id_lead', 'users.name AS lead',
                DB::raw("IF(pis_project_task_estimate.time_estimate, TIME_FORMAT(pis_project_task_estimate.time_estimate, '%H:%i'), '00:00') AS time_estimate_total"),
                DB::raw("IF(pis_project_task_session_duration.duration, pis_project_task_session_duration.duration, '00:00:00') AS duration_total"),
                DB::raw("IF(pis_work_project.status = 'waiting', '1', IF(pis_work_project.status = 'progress', '2', IF(pis_work_project.status = 'cancel', '3', '4'))) AS status_sort"),
                DB::raw("IF(pis_project_task_total.task_total, pis_project_task_total.task_total, '0') AS task_total"),
                DB::raw("IF(pis_project_task_done.task_done, pis_project_task_done.task_done, '0') AS task_done")
            )
            ->where('work_project.id', '!=', '0')
            ->when(!$users || !in_array($role_id, [1, 2, 5]), function ($query) use ($users) {
                return $query->where('work_project.id_lead', $users->id);
            })
            ->when($idProject, function ($query) use ($idProject) {
                return $query->where('work_project.id', $idProject);
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->input('search');
                return $query->where(function ($query) use ($search) {
                    $query->where('work_project.name', 'LIKE', "%$search%")
                        ->orWhere('work_project.client', 'LIKE', "%$search%")
                        ->orWhere('work_project.contact_name', 'LIKE', "%$search%")
                        ->orWhere('users.name', 'LIKE', "%$search%");
                });
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $status = $request->input('status');
                return $query->where('work_project.status', $status);
            })
            ->groupBy('work_project.id')
            ->orderByRaw($order);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $resultAll = $query->get();
        $result = $query->offset($offset)->limit($limit)->get();
        // dd($result);

        if ($result->isEmpty()) {
            return response()->json(['status' => 'ERROR', 'message' => 'WorkProject Not Found', 'error_code' => 'project_not_found', 'sql' => $query->toSql()]);
        } else {
            $data = [];
            foreach ($result as $row) {
                // Task Progress
                $taskProgress = ($row->task_total > 0) ? ($row->task_done / $row->task_total) * 100 : 0;
                $row->task_progress = number_format($taskProgress) . "%";

                // Calculate time difference
                $parsedEstimate = explode(':', $row->time_estimate_total);
                $timeEstimate = $parsedEstimate[0] * 3600 + $parsedEstimate[1] * 60;
                $parsedReal = explode(':', $row->duration_total);
                $timeReal = $parsedReal[0] * 3600 + $parsedReal[1] * 60 + $parsedReal[2];
                $timeDiff = $timeReal - $timeEstimate;
                $row->diff_time = sprintf('%02d:%02d', ($timeDiff / 3600), abs(($timeDiff / 60 % 60)));
                $row->diff_percent = ($timeEstimate > 0) ? number_format($timeDiff / $timeEstimate * 100) . "%" : "-";
                $row->timeDiff = $timeDiff;
                $row->timeEstimate = $timeEstimate;
                $row->timeReal = $row->duration_total;
                $sqlArtist = DB::table('project_task_session')
                    ->select('project_task_session.*', 'users.name AS artist')
                    ->join('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                    ->join('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->join('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                    ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                    ->where('project_task_session.time_end', '!=', '0000-00-00 00:00:00')
                // ->where('project_task_session.time_start', '>=', "{$dateStart} 00:00:00")
                // ->where('project_task_session.time_end', '<=', "{$dateEnd} 23:59:59")
                    ->where('project_category.id_project', '=', $row->id)
                    ->groupBy('project_category.id', 'project_task_session.id_artist')
                    ->orderBy('users.name')
                    ->get();

                $row->artist_list = $sqlArtist;

                $data[] = $row;
            }

            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'data_total' => count($resultAll),
                'data' => $data,
            ]);
        }
    }

    public function store(Request $request)
    {
        $users = auth()->user();
        $role_id = $users->role_id;
        if (!$users || !in_array($role_id, [1, 2, 3, 5])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_project' => 'integer',
            'id_lead' => 'required',
            'id_country' => 'required',
            'name' => 'required',
            'client' => 'required',
            'job_desc' => 'required',
            'contact_name' => 'required',
            'contact_email' => 'required|email',
            'description' => 'required',
            'requirement' => 'required',
            'status' => 'required',
            'is_ip' => 'required',
            'user_list_ip.*' => 'exists:users,id', // Assuming user_list_ip is an array of users IDs
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $getuserlist = [];
        if ($request->has('id_project')) {
            $work_project = WorkProject::findOrFail($request->id_project);
            $projectIp = ProjectIp::where('project_id', $request->id_project)->first();
            $work_project->update($request->all());
            $projectIp->update(['is_ip' => $request->is_ip]);
            if ($request->is_ip == true && $request->has('user_list_ip')) {
                $userList = [];
                foreach ($request->user_list_ip as $userId) {
                    $userList[] = [
                        'project_id' => $work_project->id,
                        'user_id' => $userId,
                    ];
                }
                $getuserlist = User::select('name', 'divisi_id')->whereIn('id', $request->user_list_ip)->get();
                // Delete existing UserIp records for the work_project
                UserIp::where('project_id', $work_project->id)->delete();

                // Insert new UserIp records
                UserIp::insert($userList);
            } else {
                UserIp::where('project_id', $work_project->id)->delete();
            }
        } else {
            $work_project = WorkProject::create($request->all());

            ProjectIp::create([
                'project_id' => $work_project->id,
                'is_ip' => $request->is_ip,
            ]);

            // Create Userip records
            if ($request->is_ip == true && $request->has('user_list_ip')) {
                $userList = [];
                foreach ($request->user_list_ip as $userId) {
                    $userList[] = [
                        'project_id' => $work_project->id,
                        'user_id' => $userId,
                    ];
                }
                UserIp::insert($userList);

                // Get users list based on users IDs
                $getuserlist = User::select('name', 'divisi_id')->whereIn('id', $request->user_list_ip)->get();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $work_project,
            'is_ip' => $request->is_ip,
            'user_list_ip' => $getuserlist, // Perbaikan operator => yang sebelumnya menjadi >
            'message' => 'WorkProject ' . ($request->has('id_project') ? 'updated' : 'created') . ' successfully',
        ], Response::HTTP_CREATED);

    }

    /**
     * Display the specified resource.
     *
     * @param WorkProject $work_project
     * @return Response
     */
    public function show($id)
    {
        $work_project = WorkProject::where('id', $id)->first();

        return response()->json([
            'status' => 'success',
            'data' => $work_project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param WorkProject $work_project
     * @return Response
     */
    public function update(Request $request, WorkProject $work_project)
    {
        $users = auth()->user();
        $role_id = $users->role_id;
        if (!$users || !in_array($role_id, [1, 2, 3, 5])) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_lead' => 'required',
            'id_country' => 'required',
            'name' => 'required',
            'job_desc' => 'required',
            'contact_name' => 'required',
            'contact_email' => 'required',
            'description' => 'required',
            'requirement' => 'required',
            'status' => 'required',
            'log_user' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], 422);
        }

        $requestData = $request->all();
        $requestData['ae_id'] = $users->id;

        $work_project->update($requestData);

        return response()->json([
            'status' => 'success',
            'data' => $work_project,
            'message' => 'WorkProject updated successfully',
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param WorkProject $work_project
     * @return Response
     */
    public function destroy(WorkProject $work_project)
    {
        try {
            $users = auth()->user();
            $role_id = $users->role_id;
            if (!$users || !in_array($role_id, [1, 2, 3, 5])) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Not Authorized',
                    'error_code' => 'not_authorized',
                ], 403);
            }
            if ($work_project) {
                $work_project->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'WorkProject deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'WorkProject not found',
                    'error_code' => 'PROJECT_NOT_FOUND',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    public function ListUserIp(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'project_id' => 'required|integer',
            ]);

            // Ambil daftar IP pengguna berdasarkan project_id
            $userIps = UserIp::leftJoin('users', 'user_ips.user_id', '=', 'users.id')
                ->where('user_ips.project_id', $request->project_id)
                ->select('user_ips.*', 'users.id as user_id', 'users.name', 'users.email', 'users.created_at', 'users.updated_at')
                ->get();

            return response()->json(['user_ips' => $userIps], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handling validation exception
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handling general exceptions
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function country()
    {
        // Ambil daftar IP pengguna berdasarkan project_id
        $Country = Country::all();
        return response()->json([
            'status' => "SUCCESS",
            'data_total' => $Country->count(),
            'data' => $Country,
        ]);
    }

    public function leadArtist()
    {
        $users = Auth::user();
        if (!$users) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        } else {
            $users = DB::table('users')
                ->select('users.id as id', 'users.name')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->where('users.role_id', '3')
                ->orWhere('users.role_id', '5')
                ->orderBy('users.name', 'ASC')
                ->get();
            if ($users) {
                return response()->json([
                    'status' => "SUCCESS",
                    'data_total' => $users->count(),
                    'data' => $users,
                ]);

            } else {
                return response()->json([
                    'status' => 'ERROR', 'message' => 'Lead Artist Not Found', 'error_code' => 'lead_artist_not_found',
                ]);
            }

        }

    }

    private function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function projectList(Request $request, $idProject = null)
    {
        $users = Auth::user();
        if (!$users) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }
        $cekuser = User::where('id', $users->id)->first();
        $role_id = $cekuser->role_id;
        if (!$users || !in_array($role_id, [1, 2, 3, 5, 4])) {
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
        $dateStartFormatted = strtoupper(date('j M Y', strtotime($dateStart)));
        $dateEndFormatted = strtoupper(date('j M Y', strtotime($dateEnd)));

        // Order mappings
        $orderMappings = [
            'projectDesc' => 'work_project.name DESC',
            'projectAsc' => 'work_project.name ASC',
            'clientDesc' => 'work_project.client DESC',
            'clientAsc' => 'work_project.client ASC',
            'jobDesc' => 'work_project.job_desc DESC',
            'jobAsc' => 'work_project.job_desc ASC',
            'contactDesc' => 'work_project.contact_name DESC',
            'contactAsc' => 'work_project.contact_name ASC',
            'countryDesc' => 'country.name DESC',
            'countryAsc' => 'country.name ASC',
            'leadDesc' => 'users.name DESC',
            'leadAsc' => 'users.name ASC',
            'statusDesc' => 'status_sort DESC, work_project.name ASC',
            'statusAsc' => 'status_sort ASC, work_project.name ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $defaultOrder = ($filter == 'log') ? "pis_work_project.id DESC" : "pis_work_project.id ASC, pis_work_project.name ASC";

        $getOrder = $request->input('order', '');
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $status = $request->get('status', '');

        $query = DB::table('work_project')
            ->leftJoin('users', 'users.id', '=', 'work_project.id_lead')
            ->leftJoin('country', 'country.id', '=', 'work_project.id_country')
            ->leftJoin(DB::raw('(SELECT pis_project_category.id_project,
            SEC_TO_TIME(SUM(TIME_TO_SEC(pis_project_task.time_estimate))) AS time_estimate
        FROM pis_project_task
        LEFT JOIN pis_project_subasset ON pis_project_subasset.id = pis_project_task.id_subasset
        LEFT JOIN pis_project_category ON pis_project_category.id = pis_project_subasset.id_category
        GROUP BY pis_project_category.id_project) pis_project_task_estimate'), 'project_task_estimate.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT pis_project_category.id_project,
            SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)))) AS duration
        FROM pis_project_task_session
        LEFT JOIN pis_project_task ON pis_project_task.id = pis_project_task_session.id_task
        LEFT JOIN pis_project_subasset ON pis_project_subasset.id = pis_project_task.id_subasset
        LEFT JOIN pis_project_category ON pis_project_category.id = pis_project_subasset.id_category
        WHERE pis_project_task_session.time_end != "0000-00-00 00:00:00"
        GROUP BY pis_project_category.id_project) pis_project_task_session_duration'), 'project_task_session_duration.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT COUNT(pis_project_task.id) AS task_total, pis_project_category.id_project
        FROM pis_project_task
        LEFT JOIN pis_project_subasset sa ON sa.id = pis_project_task.id_subasset
        LEFT JOIN pis_project_category ON pis_project_category.id = sa.id_category
        GROUP BY pis_project_category.id_project) pis_project_task_total'), 'project_task_total.id_project', '=', 'work_project.id')
            ->leftJoin(DB::raw('(SELECT COUNT(pis_project_task.id) AS task_done, pis_project_category.id_project
        FROM pis_project_task
        LEFT JOIN pis_project_subasset sa ON sa.id = pis_project_task.id_subasset
        LEFT JOIN pis_project_category ON pis_project_category.id = sa.id_category
        WHERE pis_project_task.status = "done" OR pis_project_task.status = "paid"
        GROUP BY pis_project_category.id_project) pis_project_task_done'), 'project_task_done.id_project', '=', 'work_project.id')
            ->select(
                'work_project.id', 'work_project.name', 'work_project.client', 'work_project.job_desc', 'work_project.contact_name', 'work_project.contact_email', 'work_project.description', 'work_project.requirement', 'work_project.status',
                'country.id AS id_country', 'country.name AS country', 'users.id AS id_lead', 'users.name AS lead',
                DB::raw("IF(pis_project_task_estimate.time_estimate, TIME_FORMAT(pis_project_task_estimate.time_estimate, '%H:%i'), '00:00') AS time_estimate_total"),
                DB::raw("IF(pis_project_task_session_duration.duration, pis_project_task_session_duration.duration, '00:00:00') AS duration_total"),
                DB::raw("IF(pis_work_project.status = 'waiting', '1', IF(pis_work_project.status = 'progress', '2', IF(pis_work_project.status = 'cancel', '3', '4'))) AS status_sort"),
                DB::raw("IF(pis_project_task_total.task_total, pis_project_task_total.task_total, '0') AS task_total"),
                DB::raw("IF(pis_project_task_done.task_done, pis_project_task_done.task_done, '0') AS task_done")
            )
            ->where('work_project.id', '!=', '0')
            ->when(!$users || !in_array($role_id, [1, 2, 3, 5]), function ($query) use ($users) {
                return $query->where('work_project.id_lead', $users->id);
            })
            ->when($idProject, function ($query) use ($idProject) {
                return $query->where('work_project.id', $idProject);
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->input('search');
                return $query->where(function ($query) use ($search) {
                    $query->where('work_project.name', 'LIKE', "%$search%")
                        ->orWhere('work_project.client', 'LIKE', "%$search%")
                        ->orWhere('work_project.contact_name', 'LIKE', "%$search%")
                        ->orWhere('users.name', 'LIKE', "%$search%");
                });
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $status = $request->input('status');
                return $query->where('work_project.status', $status);
            })
            ->groupBy('work_project.id')
            ->orderByRaw($order);

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);

        $resultAll = $query->get();
        $result = $query->offset($offset)->limit($limit)->get();

        if ($result->isEmpty()) {
            return response()->json(['status' => 'ERROR', 'message' => 'WorkProject Not Found', 'error_code' => 'project_not_found']);
        } else {
            $data = [];
            foreach ($result as $row) {
                // Task Progress
                $taskProgress = ($row->task_total > 0) ? ($row->task_done / $row->task_total) * 100 : 0;
                $row->task_progress = number_format($taskProgress) . "%";

                // Calculate time difference
                $parsedEstimate = explode(':', $row->time_estimate_total);
                $timeEstimate = $parsedEstimate[0] * 3600 + $parsedEstimate[1] * 60;
                $parsedReal = explode(':', $row->duration_total);
                $timeReal = $parsedReal[0] * 3600 + $parsedReal[1] * 60 + $parsedReal[2];
                $timeDiff = $timeReal - $timeEstimate;
                $row->diff_time = sprintf('%02d:%02d', ($timeDiff / 3600), abs(($timeDiff / 60 % 60)));
                $row->diff_percent = ($timeEstimate > 0) ? number_format($timeDiff / $timeEstimate * 100) . "%" : "-";
                $row->timeDiff = $timeDiff;
                $row->timeEstimate = $timeEstimate;
                $row->timeReal = $row->duration_total;
                $sqlArtist = DB::table('project_task_session')
                    ->select('project_task_session.*', 'users.name AS artist')
                    ->join('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                    ->join('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->join('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                    ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                    ->where('project_task_session.time_end', '!=', '0000-00-00 00:00:00')
                // ->where('project_task_session.time_start', '>=', "{$dateStart} 00:00:00")
                // ->where('project_task_session.time_end', '<=', "{$dateEnd} 23:59:59")
                    ->where('project_category.id_project', '=', $row->id)
                    ->groupBy('project_category.id', 'project_task_session.id_artist')
                    ->orderBy('users.name')
                    ->get();
                $is_ip = ProjectIp::where('project_id', $row->id)->value('is_ip');
                $listuserip = UserIp::select('user_id')->where('project_id', $row->id)->pluck('user_id');
                // dd($listuserip);
                $userIpList = User::select('id', 'name', 'divisi_id')->whereIn('id', $listuserip)->get();

                $row->is_ip = $is_ip == 0 ? 'false' : 'true';
                $row->artist_list = $sqlArtist; // Assuming $sqlArtist is defined elsewhere in your code
                $row->user_ip_list = $userIpList;

                $data[] = $row;
            }
            $dateRangeConditions = ["{$dateStart} 00:00:00", "{$dateEnd} 23:59:59"];
            $projectTotal = WorkProject::whereBetween('work_project.created_at', $dateRangeConditions)->count();
            // dd($projectTotal);
            $projectDone = WorkProject::whereBetween('work_project.created_at', $dateRangeConditions)
                ->where('status', 'done')
                ->count();

            $fromTask = DB::table('project_task')
                ->select('work_project.status as status', 'project_task.time_estimate', 'project_category.id_project', 'project_task.status', DB::raw('TIME_TO_SEC(pis_project_task.time_estimate) as time_estimate_seconds'))
                ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
                ->whereBetween('work_project.created_at', $dateRangeConditions);

            $fromTaskget = $fromTask->get();

            $estimationTotalget = $fromTask
                ->groupBy('id_project')
                ->selectRaw('id_project, SUM(TIME_TO_SEC(pis_project_task.time_estimate)) as total_seconds')
                ->get();

            foreach ($estimationTotalget as $total) {
                $idProject = $total->id_project;
                $totalSeconds = $total->total_seconds;
            }
            $estimationTotalSeconds = $estimationTotalget->sum('total_seconds');
            $estimationTotal = $this->secondsToTime($estimationTotalSeconds);
            // dd($estimationTotalSeconds);

            // $estimationDoneSeconds = $estimationTotalget->where('work_project.status', 'done');

            $estimationDone = $fromTask
                ->where('work_project.status', 'done')
                ->groupBy('id_project')
                ->selectRaw('id_project, SUM(TIME_TO_SEC(pis_project_task.time_estimate)) as total_seconds')
                ->get();

            $estimationDoneTotalSeconds = $estimationDone->sum('total_seconds');

            $estimationAvgcount = ($projectTotal > 0) ? $estimationTotalSeconds / $projectTotal : 0;
            $estimationAvg = $this->secondsToTime($estimationAvgcount);

            // dd($estimationAvg);
            $fromSession = DB::table('project_task_session')
                ->select('project_task_session.time_start', 'work_project.status as status', 'project_task_session.time_end', 'project_category.id_project', DB::raw('TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)) AS time_real'))
                ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
                ->where('project_task_session.time_end', '!=', '0000-00-00 00:00:00')
                ->whereBetween('work_project.created_at', $dateRangeConditions);
            $fromSessionget = $fromSession->get();

            $realtimeTotalget = $fromSession
                ->groupBy('id_project')
                ->selectRaw('id_project, SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS realtime')
                ->get();

            foreach ($realtimeTotalget as $total_real) {
                $idProject = $total_real->id_project;
                $realtime = $total_real->realtime;
            }
            $realtimeTotalSeconds = $realtimeTotalget->sum('realtime');
            $realtimeTotal = $this->secondsToTime($realtimeTotalSeconds);

            $realtimeDone = $fromSession
                ->where('work_project.status', 'done')
                ->groupBy('id_project')
                ->selectRaw('id_project, SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start))) AS realtime')
                ->get();

            $realtimeDoneTotalSeconds = $realtimeDone->sum('realtime');
            $realtimeAvgcount = ($projectTotal > 0) ? $realtimeTotalSeconds / $projectTotal : 0;
            $realtimeAvg = $this->secondsToTime($realtimeAvgcount);

            $taskTotal = $fromTaskget->count();
            $gettaskDone = $fromTaskget->where('status', 'done')->count();
            $tasktaskPaid = $fromTaskget->where('status', 'paid')->count();
            $taskDone = $gettaskDone + $tasktaskPaid;

            $plusMinus = ($estimationDoneTotalSeconds - $realtimeDoneTotalSeconds > 0) ? "-" : "+";
            // dd($plusMinus);

            $differenceAvgCount = ($projectDone > 0) ? (abs($estimationDoneTotalSeconds - $realtimeDoneTotalSeconds) / $projectDone) : 0;
            $differenceAvg = $plusMinus . $this->secondsToTime($differenceAvgCount);

            $differenceTotal = ($projectDone > 0) ? $plusMinus . $this->secondsToTime(abs($estimationDoneTotalSeconds - $realtimeDoneTotalSeconds)) : 0;

            $differencePercent = ($estimationDoneTotalSeconds > 0) ? number_format((abs($estimationDoneTotalSeconds - $realtimeDoneTotalSeconds) / $estimationDoneTotalSeconds) * 100, 1) . "%" : "0.0%";

            $ProgressPercent = ($taskTotal > 0) ? number_format(($taskDone / $taskTotal) * 100, 1) . "%" : "0.0%";

            $sqlStat = [
                ['name' => 'project_total', 'value' => $projectTotal],
                ['name' => 'estimation_total', 'value' => $estimationTotalSeconds],
                ['name' => 'realtime_total', 'value' => $realtimeTotalSeconds],
                ['name' => 'project_done', 'value' => $projectDone],
                ['name' => 'estimation_done', 'value' => $estimationDoneTotalSeconds],
                ['name' => 'realtime_done', 'value' => $realtimeDoneTotalSeconds],
                ['name' => 'task_total', 'value' => $taskTotal],
                ['name' => 'task_done', 'value' => $taskDone],
            ];

            // Konversi ke format yang diinginkan
            $projectstat = [
                "project_total" => $projectTotal,
                "estimation_total" => $estimationTotal,
                "realtime_total" => $realtimeTotal,
                "project_done" => $projectDone,
                "estimation_done" => $estimationDoneTotalSeconds,
                "realtime_done" => $realtimeDoneTotalSeconds,
                "task_total" => $taskTotal,
                "task_done" => $taskDone,
                "estimation_average" => $estimationAvg,
                "realtime_average" => $realtimeAvg,
                "difference_average" => $differenceAvg,
                "difference_total" => $differenceTotal,
                "difference_percent" => $differencePercent,
                "progress_percent" => $ProgressPercent,
                "date_start" => $dateStartFormatted,
                "date_end" => $dateEndFormatted,
            ];

// Output the result
            // dd($sqlStat

// Now $resultStat contains the results of your query

            return response()->json([
                'status' => 'SUCCESS',
                'project_stat' => $projectstat,
                'data_stat' => $sqlStat,
                'offset' => $offset,
                'limit' => $limit,
                'data_total' => count($resultAll),
                'data' => $data,
            ]);
        }

        // dd($resultAll);
    }

}
