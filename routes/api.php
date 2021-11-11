<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
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


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/

Route::group(
    [
        'middleware' => 'api',
        'namespace'  => 'App\Http\Controllers',
        'prefix'=>'tasks'
    ],
    function ($router) {
        Route::get('',[TaskController::class,'index']);
        Route::post('create_task',[TaskController::class,'createTask'] );
        Route::put('update/{task}',[TaskController::class,'update'] );
        Route::delete('delete/{task}',[TaskController::class,'destroy']);
        Route::post('delete_prerequisites',[TaskController::class,'deletePrerequisitesFromTask']);
        Route::post('add_prerequisites',[TaskController::class,'addPrerequisitesToTask']);
        Route::get('get/{task}',[TaskController::class,'show'] );
    }
);