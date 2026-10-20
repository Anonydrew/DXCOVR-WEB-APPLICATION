<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Serves administrator-managed redirects before routing. The redirect map is cached.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'build/*', 'storage/*', 'up')) {
            return $next($request);
        }

        try {
            $path = Redirect::normalisePath($request->getPathInfo());
            $redirect = Redirect::activeMap()[$path] ?? null;
        } catch (Throwable $exception) {
            Log::warning('Redirect lookup failed.', ['error' => $exception->getMessage()]);
            $redirect = null;
        }

        if ($redirect === null) {
            return $next($request);
        }

        Redirect::query()->whereKey($redirect['id'])->update([
            'hits' => DB::raw('hits + 1'),
            'last_hit_at' => now(),
        ]);

        $target = str_starts_with($redirect['to'], '/') ? url($redirect['to']) : $redirect['to'];

        return redirect()->to($target, in_array($redirect['code'], [301, 302, 307, 308], true) ? $redirect['code'] : 301);
    }
}
