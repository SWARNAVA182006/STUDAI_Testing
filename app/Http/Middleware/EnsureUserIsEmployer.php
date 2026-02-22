<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // Check if user is an employer
        if (!auth()->user()->isEmployer()) {
            abort(403, 'Access denied. Employer account required.');
        }

        // Ensure employer has a company
        if (!auth()->user()->company) {
            return redirect()->route('dashboard')
                ->with('error', 'Please set up your company profile first.');
        }

        return $next($request);
    }
}
