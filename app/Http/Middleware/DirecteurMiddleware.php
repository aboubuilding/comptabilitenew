<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DirecteurMiddleware
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
        if(!session()->has('LoginUser') && ($request->path() !='/login')){

            return redirect('login');
        }

        if( session()->has('LoginUser') && ($request->path() =='/login')){

            return back();
        }
        return $next($request);
    }
}
