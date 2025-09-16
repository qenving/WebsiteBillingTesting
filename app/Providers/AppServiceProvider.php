<?php

namespace App\Providers;

use App\Domain\Payments\GatewayManager;
use App\Domain\Payments\Gateways\MidtransGateway;
use App\Domain\Payments\Gateways\XenditGateway;
use App\Domain\Payments\Gateways\TripayGateway;
use App\Domain\Payments\Gateways\DuitkuGateway;
use App\Domain\Payments\Gateways\StripeGateway;
use App\Domain\Payments\Gateways\ManualGateway;
use App\Domain\Provisioning\ProvisioningManager;
use App\Domain\Provisioning\Drivers\VirtfusionDriver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Payment gateways registry
        $this->app->singleton(GatewayManager::class, function(){
            $m=new GatewayManager();
            $m->register(new MidtransGateway());
            $m->register(new XenditGateway());
            $m->register(new TripayGateway());
            $m->register(new DuitkuGateway());
            $m->register(new StripeGateway());
            $m->register(new ManualGateway());
            return $m;
        });

        // Provisioning drivers registry
        $this->app->singleton(ProvisioningManager::class, function(){
            $m=new ProvisioningManager();
            $m->register(new VirtfusionDriver());
            return $m;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try{ if(app()->environment('production')) URL::forceScheme('https'); }catch(\Throwable $e){}
        // Apply dynamic app name from settings when available
        try {
            $brand = \App\Support\Settings::get('branding.name');
            if ($brand) { config(['app.name' => $brand]); }
        } catch (\Throwable $e) {}
    }
}
