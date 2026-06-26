<?php

namespace App\Http\Middleware;

use App\Helpers\JsonDb;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class JsonAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('user')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
