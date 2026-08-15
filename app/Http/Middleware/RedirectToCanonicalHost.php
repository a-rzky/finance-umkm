<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengalihkan www ke domain polos.
 *
 * Cookie session terikat pada host yang persis, sehingga membiarkan kedua
 * alamat melayani aplikasi membuat pengguna yang berpindah antara
 * www.kaskita.site dan kaskita.site terlihat seperti belum masuk.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (! str_starts_with($host, 'www.')) {
            return $next($request);
        }

        // Pola ditambatkan setelah skema, sebab "www." berada di awal host,
        // bukan di awal URL.
        $target = preg_replace('#^(https?://)www\.#i', '$1', $request->fullUrl());

        // 301 untuk penjelajahan biasa karena itu sinyal kanonik yang dipahami
        // mesin pencari; 308 untuk metode lain karena mempertahankan metode
        // dan badan permintaan, sedangkan 301 boleh diubah klien jadi GET.
        return redirect($target, $request->isMethodSafe() ? 301 : 308);
    }
}
