<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       $user = auth()->user();

        if (empty($user->department_id) || empty($user->telephone_num) || empty($user->position)) {
            return redirect()->route('profile')
                ->with('warning', 'Sila lengkapkan profil anda (Bahagian, Jawatan & No. Tel) sebelum menggunakan sistem.');
        }

        return $next($request);
    }
}
