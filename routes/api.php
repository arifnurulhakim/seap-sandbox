<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CodeCheckController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DoddiController;
use App\Http\Controllers\EvaluasiController;
use App\Http\Controllers\exportabsenController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ProjectAssetController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskArtistController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TypeIndustryController;
use App\Http\Controllers\TypeProjectController;
use App\Http\Controllers\TypeProspectController;
use App\Http\Controllers\WorkProjectController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
// laravel command
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache cleared</h1>';
})->name('clear-cache');

Route::get('/route-clear', function () {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
})->name('route-clear');

Route::get('/config-cache', function () {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Configuration cached</h1>';
})->name('config-cache');

Route::get('/optimize', function () {
    $exitCode = Artisan::call('optimize');
    return '<h1>Configuration cached</h1>';
})->name('optimize');

Route::get('/hello456', function () {
    return 'Test 456';
});
// end laravel command

Route::controller(WorkProjectController::class)->group(function () {
    Route::get('/work-project/{idProject?}', 'getProjects'); //pm
    Route::post('/work-project', 'store');
    Route::delete('/work-project/{workProject}', 'destroy');
    Route::get('/ListUserIp', 'ListUserIp');
    Route::get('/leadArtist', 'leadArtist');
    Route::get('/country', 'country');
    Route::get('/projectList', 'projectList');
});

Route::controller(ProjectAssetController::class)->group(function () {
    Route::post('/projectAsset', 'store');
    Route::get('/projectAsset/{idProject?}', 'getProjectAssets');
    Route::get('/category', 'category');
    Route::delete('/projectAsset/{projectSubasset}', 'destroy');
});

Route::controller(ProjectTaskController::class)->group(function () {

    // Route for getting project tasks by artist
    Route::get('/projectTask/{idAsset?}', 'getProjectTasks');
    Route::get('/projectTaskSession/{idUser}/{idTask}', 'projectTaskSession');
    Route::post('/UpdateprojectTaskSession/{idSession}', 'projectTaskSession');
    Route::post('/projectTask', 'store');
    Route::delete('/projectTask/{projectTask}', 'destroy');

});
Route::controller(ProjectTaskArtistController::class)->group(function () {

    // Route for getting project tasks by artist
    Route::get('/projectTaskArtist/{filter?}', 'indexArtist'); //pm

    // Route for creating/updating project task session
    Route::post('/projectTaskArtist', 'storeArtist');
    Route::post('/artistRating', 'RatingArtist');

    // Route for requesting review of a project task
    Route::post('/projectTaskArtistReview', 'requestReviewArtist');

    // Route for getting current tasks for artists
    Route::get('/projectTaskArtistCurrent', 'getCurrentTasksArtist');
    Route::post('/artistTaskOvertime/{idSession}', 'artistTaskOvertime');
    Route::get('/artistTaskReport', 'artistTaskReport');
    Route::post('/projectTaskArtistReview', 'requestReviewArtist');

});

Route::controller(AuthController::class)->group(function () {

    // Route login tidak perlu middleware auth:api
    Route::get('/login', 'login')->name('login');
    Route::post('/login', 'login')->name('login');
    Route::get('/getprofile', 'getprofile')->name('getprofile');

    // Route yang memerlukan autentikasi menggunakan middleware auth:api
    Route::middleware(['auth:api'])->group(function () {
        // fitur admin
        Route::get('/getUserWithDivisi/{divisi?}', [AuthController::class, 'getUserWithDivisi']);
        Route::middleware(['CheckUserRole:1'])->group(function () {
            Route::post('/register', 'register')->name('register');
            Route::get('/register', 'register')->name('register');
            Route::get('/getAlluser', 'getAlluser')->name('getAlluser');
            Route::delete('/deleteUser/{id}', 'deleteUser')->name('deleteUser');
            Route::post('/updateUser/{id}', 'updateUser')->name('deleteUser');

        });
        Route::middleware(['CheckUserRole:1,2'])->group(function () {

            Route::post('/updateLabel/{id}', [EvaluasiController::class, 'updateLabel']);
            Route::get('/getEvaluasibyAdmin/{user_id}/{periode}', [EvaluasiController::class, 'getbyadmin']);
            Route::get('/getEvaluasibyAdmin/{user_id}', [EvaluasiController::class, 'result2']);
            Route::get('/getUserBydivisiAdmin/{role?}', [AuthController::class, 'getUserBydivisiAdmin']);

        });

        // end fitur admin
        // fitur project oleh ae

        Route::middleware(['checkaenot'])->group(function () {
            Route::get('/projects/{id}', [ProjectController::class, 'show']);

            Route::middleware(['checkae'])->group(function () {
                Route::apiResource('projects', ProjectController::class);
                Route::apiResource('type-projects', TypeProjectController::class);
                Route::apiResource('type-prospects', TypeProspectController::class);
                Route::apiResource('type-industry', TypeIndustryController::class);

                Route::controller(ContactController::class)->group(function () {
                    Route::get('contacts', 'index');
                    Route::get('contact/{id}', 'show');

                    Route::post('contact', 'store');
                    Route::post('contact/{id}', 'update');
                    Route::delete('contact/{id}', 'destroy');

                });
            });

            Route::get('/projects', [ProjectController::class, 'index']);

        });

        // end fitur project oleh ae
        // end view project oleh admin, lead, manajemen
        Route::middleware(['CheckUserRole:3,4'])->group(function () {
            // nilai fitur evaluasi
            Route::post('/addNilai', [EvaluasiController::class, 'addNilai']);
            Route::post('/addNilai/{user_id}', [EvaluasiController::class, 'addNilai']);
            Route::get('/addNilai/{user_id}', [EvaluasiController::class, 'addNilai']);

            // Menampilkan semua data evaluasi
            Route::get('/evaluasi/{user_id}/{periode}', [EvaluasiController::class, 'index']);
            Route::get('/evaluasi/{user_id}', [EvaluasiController::class, 'result']);
            Route::get('/getUserBydivisi', [AuthController::class, 'getUserBydivisi']);
            // end nilai fitur evaluasi
        });

        // auth
        Route::post('/logout', 'logout')->name('logout');
        Route::post('/refresh', 'refresh')->name('refresh');

        Route::get('/logout', 'logout')->name('logout');
        Route::get('/refresh', 'refresh')->name('refresh');
        // end auth

        // fitur absensi
        Route::controller(AbsensiController::class)->group(function () {

            Route::post('/addStartday', 'addStartday');
            Route::post('/addEndday', 'addEndday');

            Route::post('/addStartAfk', 'addStartAfk');
            Route::post('/addEndAfk', 'addEndAfk');
            Route::post('/addKeterangan/{id_absensi}', 'addKeterangan');

            Route::get('/getSelfAbsensi', 'getSelfAbsensi');

            Route::middleware(['CheckUserRole:1,2,3'])->group(function () {
                Route::get('/absensi', 'index');
                Route::get('/getByUser/{user_id}', 'getByUser');
            });
            Route::middleware(['CheckUserRole:3'])->group(function () {
                Route::get('/getAbsensibylead', 'getAbsensibylead');
            });
        });
        Route::get('/exportCSV', [exportabsenController::class, 'exportCSV']);
    });
});

Route::controller(ForgotPasswordController::class)->group(function () {
    Route::get('/password/email', '__invoke')->name('postemail');
    Route::post('/password/email', '__invoke')->name('email');
});

Route::controller(CodeCheckController::class)->group(function () {
    Route::get('/password/code/check', '__invoke')->name('check');
    Route::post('/password/code/check', '__invoke')->name('check');
});

Route::controller(ResetPasswordController::class)->group(function () {
    Route::get('/password/reset', '__invoke')->name('postreset');
    Route::post('/password/reset', '__invoke')->name('reset');
});

Route::post('/reset-first-password', [ResetPasswordController::class, 'resetFirstPassword'])->name('reset-first-password');

// fitur evaluasi

Route::get('/review-nilai/{user_id}', [EvaluasiController::class, 'review_nilai']);
Route::get('/notifeval', [EvaluasiController::class, 'notifeval']);
// Menyimpan data evaluasi baru
Route::post('/evaluasi', [EvaluasiController::class, 'store']);
// end fitur evaluasi

// kelola evaluasi
// Menampilkan data evaluasi berdasarkan id

// Menampilkan data evaluasi berdasarkan id
Route::get('/evaluasi/{id}', [EvaluasiController::class, 'show']);
// Mengupdate data evaluasi berdasarkan id
Route::put('/evaluasi/{id}', [EvaluasiController::class, 'update']);
// Menghapus data evaluasi berdasarkan id
Route::delete('/evaluasi/{id}', [EvaluasiController::class, 'destroy']);
//  end kelola evaluasi

// testin
Route::controller(DoddiController::class)->group(function () {
    Route::get('/checkdb', 'checkdb');
    Route::get('/hello', 'hello');
});

Route::get('/helloworld', 'DoddiController@hello');

Route::controller(TodoController::class)->group(function () {
    Route::get('todos', 'index');
    Route::post('todo', 'store');
    Route::get('todo/{id}', 'show');
    Route::put('todo/{id}', 'update');
    Route::delete('todo/{id}', 'destroy');

});
// end testing
