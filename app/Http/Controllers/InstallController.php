<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallController extends Controller
{
    public function index(Request $request)
    {
        if (config('app.installed')) {
            return redirect()->route('admin.dashboard');
        }

        $requirements = $this->checkRequirements();
        $databaseReady = $this->canConnectDatabase();

        return view('install.index', [
            'requirements' => $requirements,
            'databaseReady' => $databaseReady,
            'defaultAppName' => config('app.name', 'Billing Portal'),
            'defaultUrl' => rtrim($request->getSchemeAndHttpHost(), '/'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (config('app.installed')) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validate([
            'app_name' => 'required|string|max:120',
            'app_url' => 'required|url|max:255',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:150',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:1000',
            'company_tax_id' => 'nullable|string|max:100',
            'manual_instructions' => 'nullable|string|max:5000',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|string|size:3',
        ]);

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            Log::error('Installation migrate failed', ['exception' => $e]);
            return back()->withErrors(['app' => 'Failed to run database migrations: '.$e->getMessage()])->withInput();
        }

        DB::beginTransaction();
        try {
            $user = User::where('email', $data['admin_email'])->first();
            if ($user) {
                $user->name = $data['admin_name'];
                $user->password = Hash::make($data['admin_password']);
                $user->is_admin = true;
                $user->email_verified_at = now();
                $user->save();
            } else {
                $user = User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                    'is_admin' => true,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Settings::set('branding.name', $data['app_name']);
            Settings::set('company.name', $data['company_name']);
            Settings::set('company.email', $data['company_email']);
            Settings::set('company.phone', (string) ($data['company_phone'] ?? ''));
            Settings::set('company.address', (string) ($data['company_address'] ?? ''));
            Settings::set('company.tax_id', (string) ($data['company_tax_id'] ?? ''));
            Settings::set('company.currency', strtoupper($data['currency']));
            Settings::set('payments.manual.instructions', (string) ($data['manual_instructions'] ?? ''));
            Settings::set('payments.enabled', ['manual'], 'json');
            if ($data['tax_rate'] !== null) {
                Settings::set('finance.tax_rate', (float) $data['tax_rate'], 'float');
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Installation setup failed', ['exception' => $e]);
            return back()->withErrors(['app' => 'Failed to create admin user: '.$e->getMessage()])->withInput();
        }

        try {
            EnvWriter::set([
                'APP_NAME' => $data['app_name'],
                'APP_URL' => $data['app_url'],
                'APP_INSTALLED' => true,
                'MAIL_FROM_ADDRESS' => $data['company_email'],
                'MAIL_FROM_NAME' => $data['app_name'],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update environment file', ['exception' => $e]);
        }

        config(['app.installed' => true, 'app.name' => $data['app_name']]);
        Auth::login($user);

        return redirect()->route('admin.dashboard')->with('status', 'Installation completed successfully.');
    }

    protected function checkRequirements(): array
    {
        $checks = [
            'PHP >= 8.3' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'OpenSSL extension' => extension_loaded('openssl'),
            'PDO extension' => extension_loaded('pdo'),
            'Mbstring extension' => extension_loaded('mbstring'),
            'JSON extension' => extension_loaded('json'),
            'cURL extension' => extension_loaded('curl'),
            'Storage writable' => is_writable(storage_path()),
        ];

        return $checks;
    }

    protected function canConnectDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Throwable $e) {
            Log::warning('Database connection failed during installation check', ['exception' => $e]);
            return false;
        }
    }
}
