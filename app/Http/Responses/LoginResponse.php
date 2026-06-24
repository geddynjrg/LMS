<?php

namespace App\Http\Responses;

use App\Providers\RouteServiceProvider;
use App\Support\ReturnPath;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $returnTo = $request->cookie(ReturnPath::COOKIE_NAME);

        if (ReturnPath::isValid($returnTo)) {
            return redirect()->to($returnTo);
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended(RouteServiceProvider::HOME);
    }
}
