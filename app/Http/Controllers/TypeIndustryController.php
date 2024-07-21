<?php

namespace App\Http\Controllers;

use App\Models\TypeIndustry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TypeIndustryController extends Controller
{
    public function index()
    {
        try {
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameASC' => 'name ASC',
                'nameDESC' => 'name DESC',
            ];

            // Set the order based on the mapping or use the default order if not found
            $order = $orderMappings[$getOrder] ?? $defaultOrder;
            // Validation rules for input parameters
            $validOrderValues = implode(',', array_keys($orderMappings));
            $rules = [
                'offset' => 'integer|min:0',
                'limit' => 'integer|min:1',
                'order' => "in:$validOrderValues",
            ];

            $validator = Validator::make([
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
            ], $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid input parameters',
                    'errors' => $validator->errors(),
                ], 400);
            }
            $industriesQuery = TypeIndustry::select(
                'type_industries.id',
                'type_industries.name',
                'created_user.name as created_by_name',
                'modified_user.name as modified_by_name',
                'type_industries.created_at',
                'type_industries.updated_at'
            )
                ->leftJoin('users as created_user', 'type_industries.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'type_industries.modified_by', '=', 'modified_user.id')
                ->orderByRaw($order)
                ->offset($offset)
                ->limit($limit);

            if ($search !== '') {
                $industriesQuery->where(function ($query) use ($search) {
                    $query->where('type_industries.name', 'like', "%$search%")
                        ->orWhere('created_user.name', 'like', "%$search%")
                        ->orWhere('modified_user.name', 'like', "%$search%");
                });
            }

            $industries = $industriesQuery->get();
            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'data' => $industries,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $industry = TypeIndustry::select('type_industries.id', 'type_industries.name', 'created_user.name as created_by_name',
                'modified_user.name as modified_by_name', 'type_industries.created_at', 'type_industries.updated_at', )
                ->leftJoin('users as created_user', 'type_industries.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'type_industries.modified_by', '=', 'modified_user.id')
                ->where('id', $id)->first();
            if (!isset($industry)) {
                return response()->json(['status' => 'ERROR', 'message' => 'NOT FOUND'], 404);
            }
            return response()->json(['status' => 'SUCCESS', 'data' => $industry], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'ERROR', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $industry = TypeIndustry::create([
                'name' => $request->input('name'),
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'SUCCESS', 'message' => 'Industry created successfully', 'data' => $industry], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $industry = TypeIndustry::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'ERROR', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $industry->update([
                'name' => $request->input('name'),
                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'SUCCESS', 'message' => 'Industry updated successfully', 'data' => $industry], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $industry = TypeIndustry::findOrFail($id);
            $industry->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => 'Industry deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
