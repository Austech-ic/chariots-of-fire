<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Installer;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use PDO;
use Throwable;

/**
 * One-page web installer for shared hosting without SSH. Reachable only
 * until installation completes (see RedirectIfNotInstalled).
 */
class InstallController extends Controller
{
    public function welcome(): View
    {
        return view('install.welcome', ['requirements' => Installer::requirements()]);
    }

    public function run(Request $request): RedirectResponse|View
    {
        @set_time_limit(300);

        $data = $request->validate([
            'app_url' => ['required', 'url'],
            'site_name' => ['required', 'string', 'max:120'],
            'db_connection' => ['required', 'in:mysql,mariadb,pgsql,sqlite'],
            'db_host' => ['required_unless:db_connection,sqlite', 'nullable', 'string', 'max:190'],
            'db_port' => ['nullable', 'integer'],
            'db_database' => ['required_unless:db_connection,sqlite', 'nullable', 'string', 'max:190'],
            'db_username' => ['required_unless:db_connection,sqlite', 'nullable', 'string', 'max:190'],
            'db_password' => ['nullable', 'string', 'max:190'],
            'mail_from' => ['required', 'email'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'demo_content' => ['nullable', 'boolean'],
        ]);

        $connection = $data['db_connection'];
        $port = $data['db_port'] ?? ['mysql' => 3306, 'mariadb' => 3306, 'pgsql' => 5432][$connection] ?? null;
        $database = $connection === 'sqlite' ? database_path('database.sqlite') : $data['db_database'];

        try {
            if ($connection === 'sqlite') {
                touch($database);
                new PDO('sqlite:'.$database);
            } else {
                $driver = $connection === 'pgsql' ? 'pgsql' : 'mysql';
                new PDO("{$driver}:host={$data['db_host']};port={$port};dbname={$database}", $data['db_username'], $data['db_password'] ?? '', [PDO::ATTR_TIMEOUT => 5]);
            }
        } catch (Throwable $e) {
            return back()->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['db_host' => 'Could not connect to the database: '.$e->getMessage()]);
        }

        Installer::writeEnv([
            'APP_NAME' => $data['site_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($data['app_url'], '/'),
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $data['db_host'] ?? '',
            'DB_PORT' => $port ?? '',
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'MAIL_FROM_ADDRESS' => $data['mail_from'],
        ]);

        // Apply the new connection to this request as well.
        config([
            'database.default' => $connection,
            "database.connections.{$connection}.host" => $data['db_host'] ?? null,
            "database.connections.{$connection}.port" => $port,
            "database.connections.{$connection}.database" => $database,
            "database.connections.{$connection}.username" => $data['db_username'] ?? null,
            "database.connections.{$connection}.password" => $data['db_password'] ?? '',
        ]);
        DB::purge($connection);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            if ($request->boolean('demo_content')) {
                Artisan::call('db:seed', ['--class' => 'DemoContentSeeder', '--force' => true]);
            }

            $admin = User::updateOrCreate(['email' => strtolower($data['admin_email'])], [
                'name' => $data['admin_name'],
                'password' => $data['admin_password'],
                'is_staff' => true,
                'status' => User::STATUS_ACTIVE,
            ]);
            $admin->forceFill(['email_verified_at' => now()])->save();
            $admin->roles()->syncWithoutDetaching(Role::where('slug', 'super-admin')->pluck('id'));

            Settings::set('site_name', $data['site_name']);
            Settings::set('contact_email', $data['mail_from']);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['db_host' => 'Installation failed: '.$e->getMessage()]);
        }

        Installer::markInstalled();
        rescue(fn () => Artisan::call('optimize:clear'), report: false);

        return view('install.done', ['adminUrl' => rtrim($data['app_url'], '/').'/admin']);
    }
}
