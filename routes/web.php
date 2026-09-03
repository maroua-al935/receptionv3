<?php

use App\Http\Controllers\GuestController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\tests;
use App\Http\Controllers\testlw;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/',[HomeController::class,'get_index'])->middleware('auth:web')->name('home');
Route::get('/reception/live-dashboard',[HomeController::class,'reception_live_dashboard'])->middleware('auth:web')->name('reception_live_dashboard');

Route::get('/guests',[GuestController::class, 'get_index'])->middleware(['auth:web','receptionistspresident'])->name('i_visitors');
Route::get('/trash',[GuestController::class, 'trash'])->middleware(['auth:web','president'])->name('i_trash');
Route::get('/guests/add',[GuestController::class, 'add_index'])->middleware(['auth:web','reception'])->name('i_add_visitors');
Route::get('/guests/find-card-visitor',[GuestController::class, 'find_card_visitor'])->middleware(['auth:web'])->name('find_card_visitor');
Route::post('/guests/add',[GuestController::class, 'store'])->middleware(['auth:web','rantr'])->name('p_add_visitors');

Route::get('/guests_ant',[GuestController::class, 'index_2_ant'])->middleware(['auth:web','arp'])->name('i_visitors_ant');
Route::get('/guests/add_ant',[GuestController::class, 'ant_add_index'])->middleware(['auth:web','ant_reception'])->name('i_ant_add_visitors');



#Route::post('/guests/add',[GuestController::class, 'storage'])->name('p_add_visitors');
Route::get('/guests/edit/{id}',[GuestController::class, 'edit_index'])->middleware(['auth:web','rantr'])->name('i_edit_visitors');
Route::post('/guests/edit/{id}',[GuestController::class, 'edit'])->middleware(['auth:web','rantr'])->name('p_edit_visitors');
Route::post('/guests/workflow/{id}',[GuestController::class, 'workflow'])->middleware(['auth:web'])->name('p_workflow_visitors');
Route::post('/guests/delete/{id}',[GuestController::class, 'delete'])->middleware(['auth:web','rantr'])->name('p_delete_visitors');
Route::post('/trash/restore/{type}/{id}',[GuestController::class, 'restore'])->middleware(['auth:web','president'])->name('p_restore_visit');

Route::get('/info/{id}',[InfoController::class, 'info_index'])->middleware(['auth:web','rp'])->name('i_info');
Route::get('/info_ant/{id}',[InfoController::class, 'ant_info_index'])->middleware(['auth:web','ant_users'])->name('i_ant_info');
Route::get('/info_ant_p/{id}',[InfoController::class, 'ant_info_index_p'])->middleware(['auth:web','president'])->name('i_ant_p_info');
//Route::get('/info_ant_all/{id}',[InfoController::class, 'all_ant_info_index'])->middleware('auth:web')->name('i_all_ant_info');
Route::get('/login',[LoginController::class, 'index'])->middleware('guest:web')->name('l_index');
Route::post('/login',[LoginController::class,'login'])->middleware('guest:web')->name('p_login');
Route::post('/logout',[LoginController::class,'logout'])->middleware('auth:web')->name('p_logout');
Route::get('/history',[HistoryController::class,'get_index'])->middleware('auth:web')->name('i_history','rp');
Route::get('/history_ant',[HistoryController::class,'get_ant_history'])->middleware(['auth:web','arp'])->name('i_ant_history');

Route::get('/profiles', [ProfilesController::class, 'index'])->middleware(['president','auth:web']);
Route::post('/profiles', [ProfilesController::class, 'create'])->middleware(['president','auth:web']);

Route::group(['middleware' => ['auth:web', 'president']], function () {
    Route::resource('users', UserController::class);
});



Route::get('/test',[tests::class, 'get_test'])->name('i_test');
#Route::post('/test',[tests::class, 'post'])->name('p_test');
#Route::get('/lw',[testlw::class,'index'])->name('aaa');
