<?php

namespace App\Http\Controllers;

use App\Models\TypeProspect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TypeProspectController extends Controller
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
            $typeProspectsQuery = TypeProspect::select(
                'type_prospects.id',
                'type_prospects.name',
                'created_user.name as created_by_name',
                'modified_user.name as modified_by_name',
                'type_prospects.created_at',
                'type_prospects.updated_at'
            )
                ->leftJoin('users as created_user', 'type_prospects.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'type_prospects.modified_by', '=', 'modified_user.id')
                ->orderByRaw($order)
                ->offset($offset)
                ->limit($limit);

            if ($search !== '') {
                $typeProspectsQuery->where(function ($query) use ($search) {
                    $query->where('type_prospects.name', 'like', "%$search%")
                        ->orWhere('created_user.name', 'like', "%$search%")
                        ->orWhere('modified_user.name', 'like', "%$search%");
                });
            }
            $typeProspects = $typeProspectsQuery->get();
            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'data' => $typeProspects,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $typeProspect = TypeProspect::select('type_prospects.id', 'type_prospects.name', 'created_user.name as created_by_name',
                'modified_user.name as modified_by_name', 'type_prospects.created_at', 'type_prospects.updated_at', )
                ->leftJoin('users as created_user', 'type_prospects.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'type_prospects.modified_by', '=', 'modified_user.id')
                ->where('id', $id)->first();
            if (!isset($typeProspect)) {
                return response()->json(['status' => 'ERROR', 'message' => 'NOT FOUND'], 404);
            }
            return response()->json(['status' => 'SUCCESS', 'data' => $typeProspect], 200);
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

            $typeProspect = TypeProspect::create([
                'name' => $request->input('name'),
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'SUCCESS', 'message' => 'Type prospect created successfully', 'data' => $typeProspect], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $typeProspect = TypeProspect::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'ERROR', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $typeProspect->update([
                'name' => $request->input('name'),

                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'SUCCESS', 'message' => 'Type prospect updated successfully', 'data' => $typeProspect], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $typeProspect = TypeProspect::findOrFail($id);
            $typeProspect->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => 'Type prospect deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
