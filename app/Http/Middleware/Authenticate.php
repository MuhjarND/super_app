<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);

        // Token-based API authentication must enforce the same status rule.
        if (!$request->user()->canLogin()) {
            throw new \Illuminate\Auth\AuthenticationException(
                \App\User::INACTIVE_ACCOUNT_MESSAGE,
                $guards,
                $this->redirectTo($request)
            );
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
