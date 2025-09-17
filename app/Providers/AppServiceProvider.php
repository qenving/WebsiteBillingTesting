<?php

namespace App\Providers;

use App\Domain\Payments\GatewayManager;
use App\Domain\Payments\Gateways\DuitkuGateway;
use App\Domain\Payments\Gateways\ManualGateway;
use App\Domain\Payments\Gateways\MidtransGateway;
use App\Domain\Payments\Gateways\StripeGateway;
use App\Domain\Payments\Gateways\TripayGateway;
use App\Domain\Payments\Gateways\XenditGateway;
use App\Domain\Provisioning\Drivers\VirtfusionDriver;
use App\Domain\Provisioning\ProvisioningManager;
use App\Support\ModuleToggle;
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
            $m = new GatewayManager();
            $enabled = ModuleToggle::gateways();
            $restrict = is_array($enabled) && ! empty($enabled);

            $gateways = [
                new MidtransGateway(),
                new XenditGateway(),
                new TripayGateway(),
                new DuitkuGateway(),
                new StripeGateway(),
                new ManualGateway(),
            ];

            foreach ($gateways as $gateway) {
                if ($restrict && ! in_array($gateway->key(), $enabled, true)) {
                    continue;
                }

                if ($gateway->isConfigured()) {
                    $m->register($gateway);
                }
            }

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
