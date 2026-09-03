<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class receptionpresident
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
{
    if (!Auth::check()) {
        return redirect()->route('l_index');
    }

    if (in_array((int) Auth::user()->profile, [1, 2, 3, 4, 5, 8, 9, 10], true)) {
        return $next($request);
    }

    return redirect()->route('l_index');
}
}
