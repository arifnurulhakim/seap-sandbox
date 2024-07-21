<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use App\Models\PeriodeEvaluasi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::validate($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'email or password invalid',
                'error_code' => 'EMAIL_OR_PASSWORD_INVALID',
            ], 401);
        }

        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }
        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d H:i:s');

        // Tambahkan properti exp ke dalam data respons
        $expiration_time = auth()->factory()->getTTL();

        $user = Auth::user();
        $role_id = $user->role_id;
        $role = Role::where('id', $role_id)->first();
        $role_name = $role->name;
        $role_slug = $role->slug;
        $divisi_id = $user->divisi_id;
        $divisi = Divisi::where('id', $divisi_id)->first();
        $divisi_name = $divisi->name;
        $divisi_slug = $divisi->slug;
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->name,
                'role_id' => $role_id,
                'role' => $role_name,
                'role_slug' => $role_slug,
                'divisi_id' => $divisi_id,
                'divisi' => $divisi_name,
                'divisi_slug' => $divisi_slug,
                'token' => $token,
                'exp' => $expiration_time,
            ],
        ]);
    }

    public function register(Request $request)
    {
        // $request->validate([
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|string|email|max:255|unique:users',
        //     'password' => 'required|string|min:6',
        //     'role_id' => 'required|integer',
        //     'divisi_id' => 'required|integer',
        //     'join_date' => 'required|date|date_format:Y-m-d'
        // ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer',
            'divisi_id' => 'required|integer',
            'join_date' => 'required|date|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'divisi_id' => $request->divisi_id,
            'join_date' => $request->join_date,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function logout()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized, please login again',
                'error_code' => 'USER_NOT_FOUND',
            ], 401);
        }
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
                'error_code' => 'INVALID_TOKEN',
            ], 401);
        }

        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ],
        ]);
    }

    public function getAllUser()
    {
        $user = Auth::user();

        $users = User::orderBy('name', 'asc');
        if ($user->divisi_id != 4) {
            $users->whereNotIn('users.id', function ($query) {
                $query->select('user_id')->from('users_excludes');
            });
        }
        $users = $users->get();

        // dd($users);

        $userArray = [];
        foreach ($users as $user) {
            $role_id = $user->role_id;
            $role = Role::getRoleName($role_id);
            $divisi_id = $user->divisi_id;
            $divisi = Divisi::getDivisiName($divisi_id);
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $role_id,
                'role' => $role,
                'divisi_id' => $divisi_id,
                'divisi' => $divisi,
                'join_date' => $user->join_date,
                'created_at' => $user->created_at,
                'updated_at' => $user->created_at,
            ];
            array_push($userArray, $userData);
        }

        return response()->json([
            'status' => 'success',
            'data' => $userArray,
        ]);
    }

// AuthController.php

    public function deleteUser(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            $user->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'User with name ' . $user->name . ' and with email ' . $user->email . ' has been deleted.',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'error_code' => 'USER_NOT_FOUND',
            ], 404);
        }
    }
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'error_code' => 'USER_NOT_FOUND',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'integer',
            'divisi_id' => 'integer',
            'join_date' => 'date|date_format:Y-m-d',
            'password' => 'string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
                'error_code' => 'INPUT_VALIDATION_ERROR',
            ], 422);
        }

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->role_id = $request->role_id ?? $user->role_id;
        $user->divisi_id = $request->divisi_id ?? $user->divisi_id;
        $user->join_date = $request->join_date ?? $user->join_date;
        $user->password = $request->password ? bcrypt($request->password) : $user->password;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }

    public function getUserBydivisi()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'error_code' => 'USER_NOT_FOUND',
            ], 404);
        }
        $getuserid = $user->id;
        $today = date('Y-m-d');
        $thisYear = date('Y');
        $lastYear = date("Y", strtotime("-1 year"));
        define('EVALUATION_NOTIFICATION', '30');
        $role = '';
        $getdivisi = $user->divisi_id;
        $getrole = $user->role_id;
        $divisi = $user->divisi_id;

        if ($user->role_id == 4) {
            $role = 3;
        } else if ($user->role_id == 3) {
            $role = 4;
        }

        $cek_user_ex = DB::table('users_excludes')->where('user_id', $getuserid)->first();

        $users = User::leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('divisis', 'users.divisi_id', '=', 'divisis.id')
            ->select('users.*', 'roles.name as roles_name', 'divisis.name as divisis_name',
                DB::raw('DATEDIFF(DATE_FORMAT(join_date, "' . $thisYear . '-%m-%d"), "' . $today . '") AS next_evaluation'),
                DB::raw('DATE_FORMAT(join_date, "' . $thisYear . '-%m-%d") AS evaluation_date'),
                DB::raw('DATE_FORMAT(join_date, "%e %M ' . $thisYear . '") AS evaluation_date_string')
            )
            ->where(function ($query) use ($role, $getrole, $divisi, $cek_user_ex, $getdivisi, $user, $getuserid) {
                if ($cek_user_ex) {

                    if ($getrole == 4) {
                        $query->where('role_id', $role)
                            ->whereIn('divisi_id', [$getdivisi]);
                    } else {
                        $query->where('role_id', 3);
                        $query->wherenot('users.id', $getuserid);
                        $query->orwhere('role_id', 4);
                        $query->WhereIn('divisi_id', [$getdivisi]);
                    }

                } else if ($user->divisi_id != 4) {
                    $query->whereNotIn('users.id', function ($query2) use ($getuserid) {
                        $query2->select('user_id')->from('users_excludes');
                    });

                    if ($getrole == 4) {
                        $query->where('role_id', $role)
                            ->whereIn('divisi_id', [$getdivisi]);
                    } else {
                        $query->where(function ($query3) use ($getuserid, $getdivisi) {
                            $query3->where('role_id', 3)
                                ->where('users.id', '!=', $getuserid)
                                ->orWhere('role_id', 4)
                                ->whereIn('divisi_id', [$getdivisi]);
                        });
                    }
                } else if ($divisi == 1 || $divisi == 2) {
                    $query->whereNotIn('users.id', function ($subquery) {
                        $subquery->select('user_id')->from('users_excludes');
                    });
                } else {

                    if ($getrole == 4) {
                        $query->where('role_id', $role)
                            ->whereIn('divisi_id', [$getdivisi]);
                    } else {
                        $query->where('role_id', 3);
                        $query->wherenot('users.id', $getuserid);
                        $query->orwhere('role_id', 4);
                        $query->WhereIn('divisi_id', [$getdivisi]);
                    }
                }

            })

        // ->union(User::leftJoin('roles', 'users.role_id', '=', 'roles.id')
        //     ->leftJoin('divisis', 'users.divisi_id', '=', 'divisis.id')
        //     ->select('users.*', 'roles.name as roles_name', 'divisis.name as divisis_name',
        //         DB::raw('DATEDIFF(DATE_ADD(join_date, INTERVAL 3 MONTH), "' . $today . '") AS next_evaluation'),
        //         DB::raw('DATE_ADD(join_date, INTERVAL 3 MONTH) AS evaluation_date'),
        //         DB::raw('DATE_FORMAT(DATE_ADD(join_date, INTERVAL 3 MONTH), "%e %M %Y") AS evaluation_date_string')
        //     )
        //     ->where(function ($query) use ($thisYear, $lastYear) {
        //         $query->whereRaw('DATE_FORMAT(join_date, "%Y") = "' . $thisYear . '"')
        //             ->orWhere(function ($query) use ($thisYear, $lastYear) {
        //                 $query->whereRaw('DATE_FORMAT(join_date, "%Y") = "' . $lastYear . '"')
        //                     ->whereRaw('DATE_FORMAT(join_date, "%m") >= "10"');
        //             });
        //     })
        //     ->where(function ($query) use ($role,$getrole, $divisi, $cek_user_ex,$getdivisi,$user,$getuserid) {
        //         if($cek_user_ex){

        //             if ($getrole == 4) {
        //                 $query->where('role_id', $role)
        //                       ->whereIn('divisi_id', [$getdivisi]);
        //             } else {
        //                 $query->where('role_id',3 );
        //                 $query->wherenot('users.id',$getuserid );
        //                 $query->orwhere('role_id',4 );
        //                 $query->WhereIn('divisi_id', [$getdivisi]);
        //             }

        //         }
        //        else if($user->divisi_id != 4){
        //             $query->whereNotIn('users.id', function($query2) {
        //                 $query2->select('user_id')->from('users_excludes');
        //                 })
        //                 ->where('role_id', $role)
        //                 ->where('divisi_id', [$getdivisi]);
        //         }
        //         else if($divisi == 1 || $divisi == 2) {
        //             $query->whereNotIn('users.id', function ($subquery) {
        //                 $subquery->select('user_id')->from('users_excludes');
        //             });
        //         }else{
        //             if ($getrole == 4) {
        //                 $query->where('role_id', $role)
        //                       ->whereIn('divisi_id', [$getdivisi]);
        //             } else {
        //                 $query->where('role_id',3 );
        //                 $query->wherenot('users.id',$getuserid );
        //                 $query->orwhere('role_id',4 );
        //                 $query->WhereIn('divisi_id', [$getdivisi]);
        //             }
        //         }
        //     }))
            ->orderBy('evaluation_date')
            ->get();

        $userArray = [];

        foreach ($users as $u) {
            $role = $u->role_id;
            $name_role = $u->roles_name;
            $divisi = $u->divisi_id;
            $name_divisi = $u->divisis_name;
            $periode = PeriodeEvaluasi::select('periode_evaluasis.id', 'periode_evaluasis.periode', 'periode_evaluasis.label', 'periode_evaluasis.isLock')
                ->join('nilai_evaluasi_users', 'nilai_evaluasi_users.periode', '=', 'periode_evaluasis.periode')
                ->where('nilai_evaluasi_users.user_id', $u->id)
                ->where('nilai_evaluasi_users.reviewer_id', $user->id)
                ->where('periode_evaluasis.user_id', $u->id)
                ->groupBy('periode_evaluasis.id', 'periode_evaluasis.periode', 'periode_evaluasis.label', 'periode_evaluasis.isLock')
                ->distinct()
                ->get();

            $periodeArray = [];

            foreach ($periode as $data) {
                $periodeData = [
                    'periode_id' => $data->id,
                    'periode' => $data->periode,
                    'label' => $data->label,
                    'isLock' => $data->isLock,
                ];
                $periodeArray[] = $periodeData;
            }

            $userData = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_id' => $u->role_id,
                'role' => $role,
                'name_role' => $name_role,
                'divisi_id' => $u->divisi_id,
                'divisi' => $divisi,
                'name_divisi' => $name_divisi,
                'join_date' => $u->join_date,
                'created_at' => $u->created_at,
                'updated_at' => $u->created_at,
                'periode' => $periodeArray,
                'evaluation_date' => $u->evaluation_date,
                'evaluation_date_string' => $u->evaluation_date_string,
            ];

            array_push($userArray, $userData);
        }

        return response()->json([
            'status' => 'success',
            'data' => $userArray,
        ]);
    }

    public function getUserBydivisiAdmin($role = null)
    {
        $user = Auth::user();

        $users = User::with(['roles', 'divisis', 'nilai_evaluasi_user', 'periode_evaluasi']);

        if ($role) {
            $users->where('role_id', $role);
        }

        if ($user->divisi_id != 4) {
            $users->whereNotIn('users.id', function ($query) {
                $query->select('user_id')->from('users_excludes');
            });

        }
        $users = $users->orderby('name', 'asc')
            ->get();
        $userArray = [];
        foreach ($users as $u) {
            $role = $u->role_id;
            $divisi = $u->divisi_id;
            $name_role = $u->roles ? $u->roles->name : null;
            $name_divisi = $u->divisis ? $u->divisis->name : null;

            $periodeArray = [];

            foreach ($u->periode_evaluasi->groupBy(['id', 'periode', 'label', 'isLock']) as $id => $idCollection) {
                foreach ($idCollection as $periode => $labelCollection) {
                    foreach ($labelCollection as $label => $nilaiCollection) {
                        foreach ($nilaiCollection as $isLock => $lockCollection) {

                            $periodeData = [
                                'periode_id' => $id,
                                'periode' => $periode,
                                'label' => $label,
                                'isLock' => $isLock,
                            ];
                            array_push($periodeArray, $periodeData);
                        }
                    }
                }
            }

            $userData = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_id' => $u->role_id,
                'role' => $role,
                'name_role' => $name_role,
                'divisi_id' => $u->divisi_id,
                'divisi' => $divisi,
                'name_divisi' => $name_divisi,
                'join_date' => $u->join_date,
                'created_at' => $u->created_at,
                'updated_at' => $u->created_at,
                'periode' => $periodeArray,
            ];
            array_push($userArray, $userData);
        }

        return response()->json([
            'status' => 'success',
            'data' => $userArray,
        ]);
    }

    public function getprofile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }
        $role_id = $user->role_id;
        $role = Role::getRoleName($role_id);
        $divisi_id = $user->divisi_id;
        $divisi = Divisi::getDivisiName($divisi_id);
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $role_id,
            'role' => $role,
            'divisi_id' => $divisi_id,
            'divisi' => $divisi,
            'join_date' => $user->join_date,
            'created_at' => $user->created_at,
            'updated_at' => $user->created_at,
        ];
        return response()->json([
            'status' => 'success',
            'data' => $userData,
        ]);

    }

    public function getUserWithDivisi($divisi = null)
    {
        $usersQuery = User::with(['roles', 'divisis']);

        if ($divisi) {
            $usersQuery->where('divisi_id', $divisi);
        }

        $users = $usersQuery->orderBy('name', 'asc')->get();

        $userArray = [];

        foreach ($users as $u) {
            $userData = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_id' => $u->role_id,
                'role' => $u->roles ? $u->roles->name : null,
                'divisi_id' => $u->divisi_id,
                'divisi' => $u->divisis ? $u->divisis->name : null,
                'join_date' => $u->join_date,
                'created_at' => $u->created_at,
                'updated_at' => $u->created_at, // Perhatikan apakah yang dimaksud adalah 'updated_at'
            ];

            array_push($userArray, $userData);
        }

        return response()->json([
            'status' => 'success',
            'data' => $userArray,
        ]);
    }

}
