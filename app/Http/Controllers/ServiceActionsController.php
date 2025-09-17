<?php

namespace App\Http\Controllers;

use App\Domain\Provisioning\ProvisioningManager;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceActionsController extends Controller
{
    protected function assertOwned(Service $service): void
    {
        $service->loadMissing('client');
        $user = Auth::user();
        if (! $user || ! $service->client || $service->client->user_id !== $user->id) {
            abort(403);
        }
    }

    protected function perform(Service $service, ProvisioningManager $manager, callable $action, string $successMessage, array $allowedStatuses = ['active'])
    {
        $this->assertOwned($service);
        if (! in_array($service->status, $allowedStatuses, true)) {
            return redirect()->back()->with('error', 'Service is not in a state that allows this action');
        }
        $driverKey = $service->meta['driver'] ?? 'virtfusion';
        try {
            $driver = $manager->get($driverKey);
            $action($driver, $service);
            $service->refresh();
        } catch (\Throwable $e) {
            Log::error('Service action failed', [
                'service_id' => $service->id,
                'driver' => $driverKey,
                'action' => $successMessage,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Unable to complete the requested action. Please try again or contact support.');
        }
        return redirect()->back()->with('status', $successMessage);
    }

    public function reboot(Service $service, ProvisioningManager $manager)
    {
        return $this->perform($service, $manager, function ($driver, Service $svc) {
            $driver->reboot($svc);
        }, 'Reboot command sent');
    }

    public function powerOn(Service $service, ProvisioningManager $manager)
    {
        return $this->perform($service, $manager, function ($driver, Service $svc) {
            $driver->powerOn($svc);
        }, 'Power on command sent');
    }

    public function powerOff(Service $service, ProvisioningManager $manager)
    {
        return $this->perform($service, $manager, function ($driver, Service $svc) {
            $driver->powerOff($svc);
        }, 'Power off command sent');
    }

    public function resetPassword(Service $service, ProvisioningManager $manager)
    {
        return $this->perform($service, $manager, function ($driver, Service $svc) {
            $driver->resetPassword($svc);
        }, 'Password reset successfully requested');
    }

    public function console(Service $service, ProvisioningManager $manager)
    {
        $this->assertOwned($service);
        if ($service->status === 'terminated') {
            return redirect()->back()->with('error', 'Console is unavailable for terminated services');
        }
        $driverKey = $service->meta['driver'] ?? 'virtfusion';
        try {
            $driver = $manager->get($driverKey);
            $url = $driver->consoleUrl($service);
        } catch (\Throwable $e) {
            Log::error('Service console launch failed', [
                'service_id' => $service->id,
                'driver' => $driverKey,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Unable to open the console right now. Please try again or contact support.');
        }
        if (! $url) {
            return redirect()->back()->with('error', 'Console is temporarily unavailable. Please try again later.');
        }
        return redirect()->away($url);
    }
}
