<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class isReceptionistsPresident
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
        if (in_array((int) auth::guard('web')->user()->profile, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], true))
        {
            return $next($request);
        }else{
            return redirect()->route('l_index');
        }
    }
}
