<?php

use Illuminate\Support\Facades\Route;
use App\Domain\Payments\GatewayManager;
use App\Domain\Provisioning\ProvisioningManager;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\PaymentController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', fn()=>response()->json(['status'=>'ok']));
Route::get('/shop', [ShopController::class, 'index']);

Route::middleware(['auth','verified'])->group(function(){
    Route::get('/plans',[OrderController::class,'plans']);
    Route::post('/orders',[OrderController::class,'create']);
    Route::post('/pay/{gateway}/{invoice}',[PaymentController::class,'initiate']);
    Route::get('/invoices',[InvoicesController::class,'index']);
    Route::get('/invoices/{invoice}',[InvoicesController::class,'show']);
    Route::get('/services',[ServiceController::class,'index']);
    Route::get('/invoices/{invoice}/pdf',[InvoicePdfController::class,'show']);
    Route::get('/admin/settings',[AdminSettingsController::class,'show']);
    Route::post('/admin/settings',[AdminSettingsController::class,'update']);
});

Route::post('/webhook/payments/{gateway}',[PaymentWebhookController::class,'handle']);
