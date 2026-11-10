<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias 'role' untuk CheckRole middleware — dipakai di routes sebagai middleware('role:Masyarakat')
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Pengguna yang sudah login lalu membuka /login, /register, dst. diarahkan ke
        // halaman awal perannya. Tanpa ini Laravel mengarahkan ke "/" yang kembali
        // ke /login, sehingga pengalihan berputar tanpa henti.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->halamanAwal());

        // Server di belakang proxy (Coolify/Traefik): percayai header X-Forwarded-* dari proxy,
        // supaya Laravel tahu permintaan aslinya https dan membuat link/form dengan https.
        // TRUSTED_PROXIES: "*" (semua proxy, cocok untuk Coolify) atau daftar IP dipisah koma.
        // Kosong (laptop pengembang) = tidak ada proxy yang dipercaya.
        $proxy = env('TRUSTED_PROXIES');
        if (filled($proxy)) {
            $middleware->trustProxies(at: $proxy === '*' ? '*' : array_map('trim', explode(',', $proxy)));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
