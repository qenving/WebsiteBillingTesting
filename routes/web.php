<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\InvoiceCreditController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceActionsController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminPaymentsController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AdminClientController;

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

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/shop', [ShopController::class, 'index']);

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/plans',[OrderController::class,'plans']);
    Route::post('/orders',[OrderController::class,'create']);
    Route::post('/pay/{gateway}/{invoice}',[PaymentController::class,'initiate'])->middleware('throttle:10,1');
    Route::get('/invoices',[InvoicesController::class,'index']);
    Route::get('/invoices/{invoice}',[InvoicesController::class,'show']);
    Route::post('/invoices/{invoice}/apply-credit',[InvoiceCreditController::class,'apply'])->name('invoices.apply-credit');
    Route::get('/tickets',[TicketController::class,'index'])->name('tickets.index');
    Route::get('/tickets/create',[TicketController::class,'create'])->name('tickets.create');
    Route::post('/tickets',[TicketController::class,'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}',[TicketController::class,'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply',[TicketController::class,'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close',[TicketController::class,'close'])->name('tickets.close');
    Route::get('/services',[ServiceController::class,'index']);
    Route::post('/services/{service}/actions/reboot',[ServiceActionsController::class,'reboot']);
    Route::post('/services/{service}/actions/power-on',[ServiceActionsController::class,'powerOn']);
    Route::post('/services/{service}/actions/power-off',[ServiceActionsController::class,'powerOff']);
    Route::post('/services/{service}/actions/reset-password',[ServiceActionsController::class,'resetPassword']);
    Route::get('/services/{service}/actions/console',[ServiceActionsController::class,'console']);
    Route::get('/invoices/{invoice}/pdf',[InvoicePdfController::class,'show']);

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function(){
        Route::get('/dashboard',[AdminDashboardController::class,'index'])->name('dashboard');
        Route::get('/settings',[AdminSettingsController::class,'show'])->name('settings.show');
        Route::post('/settings',[AdminSettingsController::class,'update'])->name('settings.update');
        Route::get('/payments',[AdminPaymentsController::class,'index'])->name('payments.index');
        Route::post('/payments/{payment}/confirm',[AdminPaymentsController::class,'confirm'])->name('payments.confirm');
        Route::get('/clients',[AdminClientController::class,'index'])->name('clients.index');
        Route::get('/clients/{client}',[AdminClientController::class,'show'])->name('clients.show');
        Route::post('/clients/{client}/credit',[AdminClientController::class,'adjustCredit'])->name('clients.credit');
        Route::get('/tickets',[AdminTicketController::class,'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}',[AdminTicketController::class,'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply',[AdminTicketController::class,'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/status',[AdminTicketController::class,'updateStatus'])->name('tickets.status');
    });
});

Route::post('/webhook/payments/{gateway}',[PaymentWebhookController::class,'handle']);

Route::post('/logout', function (Request $request) {
    Auth::guard()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
