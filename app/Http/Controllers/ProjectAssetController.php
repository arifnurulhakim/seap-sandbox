<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use App\Models\ProjectIp;
use App\Models\ProjectSubasset;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSession;
use App\Models\RefCategory;
use App\Models\User;
use App\Models\UserIp; // Assuming you have a model for the work_project task session
use App\Models\WorkProject; // Assuming you have a model for the work_project task session
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $idProject = '')
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

        $defaultOrder = "status_sort ASC, projectSubasset.name ASC";

        // Order mappings
        $orderMappings = [
            'projectDesc' => 'projectSubasset.name DESC',
            'projectAsc' => 'projectSubasset.name ASC',
            'clientDesc' => 'projectSubasset.client DESC',
            'clientAsc' => 'projectSubasset.client ASC',
            'jobDesc' => 'projectSubasset.job_desc DESC',
            'jobAsc' => 'projectSubasset.job_desc ASC',
            'contactDesc' => 'projectSubasset.contact_name DESC',
            'contactAsc' => 'projectSubasset.contact_name ASC',
            'countryDesc' => 'rc.name DESC',
            'countryAsc' => 'rc.name ASC',
            'leadDesc' => 'users.name DESC',
            'leadAsc' => 'users.name ASC',
            'statusDesc' => 'status_sort DESC, projectSubasset.name ASC',
            'statusAsc' => 'status_sort ASC, projectSubasset.name ASC',
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
            $whereLead = " AND (projectSubasset.id_lead = '" . $user . "')";
        }

        $whereId = '';
        if ($idProject) {
            $whereId = " AND (projectSubasset.id = '" . $idProject . "')";
        }

        $whereSearch = '';
        if ($search) {
            $whereSearch = " AND (projectSubasset.name LIKE '%" . $search . "%' OR projectSubasset.client LIKE '%" . $search . "%' OR projectSubasset.contact_name LIKE '%" . $search . "%' OR users.name LIKE '%" . $search . "%')";
        }

        $whereStatus = '';
        if ($status) {
            $whereStatus = " AND projectSubasset.status = '" . $status . "'";
        }

    }

    public function getProjectAssets(Request $request, $idProject = null)
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

        // Order mappings
        $orderMappings = [
            'categoryDesc' => 'name DESC',
            'categoryAsc' => 'name ASC',
        ];

        // Set the order based on the mapping or use the default order if not found
        $defaultOrder = ($filter == 'log') ? "pis_work_project.id DESC" : "pis_work_project.id ASC, pis_work_project.name ASC";

        $getOrder = $request->input('order', '');
        $order = $orderMappings[$getOrder] ?? $defaultOrder;

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $type = $request->get('type', '');

        $work_project = WorkProject::select('work_project.id',
            'project_category.id AS category_id',
            'work_project.name',
            'work_project.client',
            'work_project.job_desc',
            'work_project.contact_name',
            'work_project.contact_email',
            'country.name AS country',
            'users.name AS lead',
            'work_project.description',
            'work_project.requirement',
            'work_project.status')
            ->leftJoin('project_category', 'project_category.id_project', '=', 'work_project.id')
            ->leftJoin('country', 'country.id', '=', 'work_project.id_country')
            ->leftJoin('users', 'users.id', '=', 'work_project.id_lead')
            ->where('work_project.id', $idProject)
            ->first();

        if ($work_project) {
            $is_ip = ProjectIp::where('project_id', $work_project->id)->first();
            if ($is_ip && $is_ip->is_ip == 1) {
                $list_users = UserIp::select('user_ips.user_id', 'users.name')
                    ->where('project_id', $work_project->id)
                    ->leftJoin('users', 'users.id', '=', 'user_ips.user_id')
                    ->get();

                $work_project->is_ip = true;
                $work_project->list_collaborators = $list_users;
            } else {
                $work_project->is_ip = false;
                $work_project->list_collaborators = [];
            }
        } else {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'project not found',
                'error_code' => 'PROJECT_NOT_FOUND',
            ], 404);
        }

        // Now $work_project contains the work_project details along with collaborators information

        // dd($work_project);

        if ($type == 'dropdown' && $role_id == 3 || $role_id == 6) {
            $query = DB::table('project_subasset')
                ->select('project_subasset.id', DB::raw("CONCAT(pis_project_category.name, ' / ', pis_project_subasset.name) AS name"))
                ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->leftJoin('work_project', 'work_project.id', '=', 'project_category.id_project')
                ->where('project_subasset.id', '!=', '0')
                ->where('project_category.id_project', '=', $work_project->id)
                ->orderBy('project_category.name', 'ASC')
                ->orderBy('project_subasset.name', 'ASC');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $resultAll = $query->get();
            $result = $query->offset($offset)->limit($limit)->get();
            if ($resultAll->count() == 0) {
                return response()->json(['status' => 'SUCCESS', 'data_total' => $resultAll->count(), 'data' => $result]);
            } else {

                foreach ($result as $row) {

                    return response()->json(['status' => 'SUCCESS', 'data_total' => $resultAll->count(), 'data' => $result]);
                }
            }
        } else {
            $query = ProjectSubasset::select(
                'project_category.id as id',
                'project_category.name as category',
            )
                ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                ->where('project_category.id_project', $work_project->id)
                ->groupBy('project_category.id', 'project_category.name')
                ->distinct();
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);

            $resultAll = $query->get();
            $result = $query->offset($offset)->limit($limit)->get();
            foreach ($result as $asset) {
                $estimate = ProjectTask::select(DB::raw("TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pis_project_task.time_estimate))), '%H:%i') AS time_estimate"))
                    ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->where('project_subasset.id_category', $asset->id)
                    ->first();

                $duration = ProjectTaskSession::select(DB::raw("SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)))) AS duration"))
                    ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                    ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                    ->where('project_subasset.id_category', $asset->id)
                    ->first();

                $getasset = ProjectSubasset::select('project_subasset.id as id', 'project_category.name as category', 'project_subasset.name as asset', 'project_subasset.detail', 'project_subasset.status')
                    ->leftJoin('project_category', 'project_category.id', '=', 'project_subasset.id_category')
                    ->where('id_category', $asset->id)->get();
                foreach ($getasset as $assetlist) {
                    $estimateAsset = ProjectTask::select(DB::raw("TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(pis_project_task.time_estimate))), '%H:%i') AS time_estimate"))
                        ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                        ->where('project_subasset.id', $assetlist->id)
                        ->first();

                    $durationAsset = ProjectTaskSession::select(DB::raw("SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)))) AS duration"))
                        ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                        ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                        ->where('project_subasset.id', $assetlist->id)
                        ->first();

                    $artistlist = ProjectTaskSession::select('users.id as id_artist', 'users.name as artist')
                        ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                        ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                        ->leftJoin('users', 'users.id', '=', 'project_task_session.id_artist')
                        ->where('project_subasset.id', $assetlist->id)->distinct()->orderby('artist', 'asc')->get();
                    // dd($artistlist);

                    foreach ($artistlist as $artistList) {

                        $durationArtist = ProjectTaskSession::select(DB::raw("SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(pis_project_task_session.time_end, pis_project_task_session.time_start)))) AS duration"))
                            ->leftJoin('project_task', 'project_task.id', '=', 'project_task_session.id_task')
                            ->leftJoin('project_subasset', 'project_subasset.id', '=', 'project_task.id_subasset')
                            ->where('project_subasset.id', $assetlist->id)
                            ->where('project_task_session.id_artist', $artistList->id_artist)
                            ->first();
                        $artistList->artist_list = $durationArtist ? $durationArtist->duration : '00:00:00';
                    }

                    $assetlist->time_estimate = $estimateAsset ? $estimateAsset->time_estimate : '00:00';
                    $assetlist->duration_total = $durationAsset ? $durationAsset->duration : '00:00:00';
                    $assetlist->artist_list = $artistlist;
                }

                $asset->time_estimate_total = $estimate ? $estimate->time_estimate : '00:00';
                $asset->duration_total = $duration ? $duration->duration : '00:00:00';
                $asset->asset = $getasset;

            }

            return response()->json(['status' => 'SUCCESS', 'data_project' => $work_project, 'offset' => $offset, 'limit' => $limit, 'data_total' => count($resultAll), 'data' => $result]);
        }

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
            'id_asset' => 'integer',
            'category' => 'string',
            'id_project' => 'required|integer',
            'name' => 'string',
            'detail' => 'string',
            'status' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $work_project = WorkProject::select('work_project.id', 'project_category.id AS category_id')
            ->leftJoin('project_category', 'project_category.id_project', '=', 'work_project.id')
            ->where('work_project.id', $request->id_project)
            ->first();
        $projectcategory = ProjectCategory::where('name', $request->category)->where('id_project', $work_project->id)->first();
        // dd($work_project);

        $id_category = "";

        if ($projectcategory) {
            // Kategori ditemukan, gunakan nilai yang ada
            $id_category = $projectcategory->id;
            // dd($id_category);
        } else {
            // Kategori tidak ditemukan, buat kategori baru
            $insert_category = ProjectCategory::create([
                'id_project' => $work_project->id,
                'name' => $request->category,
            ]);

            $id_category = $insert_category->id;
        }
        // dd($work_project);

        if ($request->id_asset) {
            $projectSubasset = ProjectSubasset::findOrFail($request->id_asset);
            $projectSubasset->update([
                'id_category' => $id_category,
                'id_project' => $request->filled('id_project') ? $request->id_project : $projectSubasset->id_project,
                'name' => $request->filled('name') ? $request->name : $projectSubasset->name,
                'detail' => $request->filled('detail') ? $request->detail : $projectSubasset->detail,
                'status' => $request->filled('status') ? $request->status : $projectSubasset->status,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'WorkProject asset updated successfully',
                'data' => $projectSubasset,
            ], Response::HTTP_OK);

        } else {
            $projectSubasset = ProjectSubasset::create(array_merge($request->all(), ['log_time' => now(), 'log_user' => $user->id, 'id_category' => $id_category]));
            return response()->json([
                'status' => 'success',
                'message' => 'work_project asset created successfully',
                'data' => $projectSubasset,
            ], Response::HTTP_CREATED);
        }

    }
    public function destroy(ProjectSubasset $projectSubasset)
    {
        try {
            if ($projectSubasset) {
                $projectSubasset->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'WorkProject asset deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'WorkProject asset not found',
                    'error_code' => 'PROJECT_ASSET_NOT_FOUND',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    private function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function category()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Not Authorized',
                'error_code' => 'not_authorized',
            ], 403);
        }

        $category = RefCategory::select('name')->orderby('name', 'asc')->get();

        return response()->json([
            'status' => 'SUCCESS',
            'data_total' => $category->count(),
            'data' => $category,
        ]);

    }

}
