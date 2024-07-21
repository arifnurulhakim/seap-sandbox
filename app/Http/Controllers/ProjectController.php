<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $projects = Project::all();

        return response()->json([
            'status' =>'success',
            'data' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    { 
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'client' => 'required',
            'contact' => 'required',
            'email' => 'required',
            'country' => 'required',
            'description' => 'required',
            'requirement' => 'required',
            'timeline_link' => 'required',
            'trello_link' => 'required',
            'status' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR'
            ], 422);
        }
    
        $requestData = $request->all();
        $requestData['ae_id'] = $user->id;
        $project = Project::create($requestData);

    
        return response()->json([
            'status' => 'success',
            'data' => $project,
            'message' => 'Project created successfully',
        ], Response::HTTP_CREATED);
    }
    

    /**
     * Display the specified resource.
     *
     * @param Project $project
     * @return Response
     */
    public function show($id)
    {
        $project = Project::where('id', $id)->first();
        
        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function update(Request $request, Project $project)
{
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'client' => 'required',
        'contact' => 'required',
        'email' => 'required',
        'country' => 'required',
        'description' => 'required',
        'requirement' => 'required',
        'timeline_link' => 'required',
        'trello_link' => 'required',
        'status' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors(),
            'error_code' => 'INPUT_VALIDATION_ERROR'
        ], 422);
    }

    $requestData = $request->all();
    $requestData['ae_id'] = $user->id;

    $project->update($requestData);

    return response()->json([
        'status' => 'success',
        'data' => $project,
        'message' => 'Project updated successfully',
    ], Response::HTTP_OK);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param Project $project
     * @return Response
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully',
        ]);
    }
}
