<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->cadanganBilaSlaveMati();

        // Halaman Kelola akun hanya untuk peran admin
        Gate::define('kelola-akun', fn (User $user) => $user->isAdmin());
    }

    /**
     * Mode Master-Slave: bila server slave (DATA_DB_PORT) tidak bisa dihubungi, data dibaca dari replika mode script
     * di server utama agar dashboard tetap tampil. Halaman Integrasi menandai keadaan ini.
     */
    private function cadanganBilaSlaveMati(): void
    {
        $data = config('database.connections.data');
        $utama = config('database.connections.mariadb');
        if ((string) $data['port'] === (string) $utama['port'] && $data['host'] === $utama['host']) {
            return;
        }
        try {
            DB::connection('data')->getPdo();
        } catch (Throwable) {
            config(['database.connections.data.host' => $utama['host'], 'database.connections.data.port' => $utama['port'],
                'database.connections.data.database' => $utama['database'],
                'database.connections.data.username' => $utama['username'], 'database.connections.data.password' => $utama['password']]);
            DB::purge('data');
            config(['app.replika_cadangan' => true]);
        }
    }
}
