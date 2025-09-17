<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAppInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        $installed = (bool) config('app.installed', false);
        $isInstallRoute = $request->is('install') || $request->is('install/*');
        $isPublic = $request->is('storage/*') || $request->is('vendor/*') || $request->is('js/*') || $request->is('css/*') || $request->is('health');

        if (! $installed) {
            if ($isInstallRoute || $isPublic) {
                return $next($request);
            }

            return redirect()->route('install.index');
        }

        if ($installed && $isInstallRoute) {
            return redirect()->to('/');
        }

        return $next($request);
    }
}
