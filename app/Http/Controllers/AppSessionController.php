<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AppSessionController extends Controller
{
    public static function describe(string $email): array
    {
        $empty = ['found' => false, 'role' => 'staff', 'constituency' => '', 'base' => ''];
        $email = strtolower(trim($email));
        if (!preg_match('/^[^\s@]+@uwezo\.go\.ke$/', $email)) {
            return $empty;
        }
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $empty;
        }
        return array_merge(['found' => true], self::profile($user));
    }

    public function fromZimbra(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $token = trim((string) $request->input('authToken', ''));
        if ($token === '' || !preg_match('/^[^\s@]+@uwezo\.go\.ke$/', $email)) {
            return response()->json(['ok' => false, 'detail' => 'Mailbox details are required.'], 422);
        }
        $proved = $this->zimbraEmail($token);
        if ($proved === null) {
            return response()->json(['ok' => false, 'detail' => 'The mailbox sign-in could not be confirmed.'], 401);
        }
        if ($proved !== $email) {
            return response()->json(['ok' => false, 'detail' => 'This mailbox does not match the sign-in.'], 403);
        }
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['ok' => false, 'found' => false, 'detail' => 'This mailbox is not an LMS user.'], 404);
        }
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(array_merge(['ok' => true], self::account($user)));
    }

    public function fromPassword(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'detail' => 'Email and password are required.'], 422);
        }
        $throttleKey = Str::transliterate($email.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'ok' => false,
                'detail' => 'Too many sign-in attempts. Wait a minute, then try the same password again.',
            ], 429);
        }
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json(['ok' => false, 'detail' => 'The LMS database did not accept this email and password.'], 401);
        }
        if (!$user->status) {
            return response()->json(['ok' => false, 'detail' => 'This LMS account is deactivated.'], 403);
        }
        RateLimiter::clear($throttleKey);
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json(array_merge(['ok' => true], self::account($user)));
    }

    public function me(Request $request)
    {
        return response()->json(self::account($request->user()));
    }

    public static function account(User $user): array
    {
        return array_merge(self::profile($user), [
            'found' => true,
            'email' => $user->email,
            'name' => (string) ($user->name ?? ''),
            'phone' => (string) ($user->phone_number ?? ''),
            'idNumber' => (string) ($user->id_number ?? ''),
        ]);
    }

    private static function profile(User $user): array
    {
        $names = $user->getRoleNames();
        $role = 'staff';
        if ($names->contains('hq_officer') || $names->contains('admin') || $names->contains('super-admin')) {
            $role = 'headquarters';
        } elseif ($names->contains('head_of_secretariat')) {
            $role = 'secretariat';
        } elseif ($names->contains('accountant')) {
            $role = 'accountant';
        } elseif ($names->contains('constituency_officer')) {
            $role = 'constituency';
        }
        $current = $user->currentConstituency()->with('county')->first()
            ?: $user->constituencies()->with('county')->first();
        $constituency = (string) ($current->constituency_name ?? '');
        $base = '';
        if ($current && $current->county && $current->slug && $current->county->slug) {
            $base = '/'.$current->county->slug.'/'.$current->slug;
        }
        return ['role' => $role, 'constituency' => $constituency, 'base' => $base];
    }

    private function zimbraEmail(string $token): ?string
    {
        $safe = htmlspecialchars($token, ENT_QUOTES | ENT_XML1);
        $soap = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'
            .'<soap:Header><context xmlns="urn:zimbra"><authToken>'.$safe.'</authToken></context></soap:Header>'
            .'<soap:Body><GetInfoRequest xmlns="urn:zimbraAccount"/></soap:Body>'
            .'</soap:Envelope>';
        try {
            $response = Http::timeout(20)
                ->withHeaders(['Content-Type' => 'application/soap+xml; charset=utf-8'])
                ->withBody($soap, 'application/soap+xml')
                ->post('https://mail.govmail.ke/service/soap');
        } catch (\Throwable $e) {
            return null;
        }
        $xml = $response->body();
        if (!str_contains($xml, 'GetInfoResponse')) {
            return null;
        }
        if (preg_match('/<name>([^<]+@uwezo\.go\.ke)<\/name>/i', $xml, $match)) {
            return strtolower($match[1]);
        }
        if (preg_match('/<a n="zimbraMailDeliveryAddress">([^<]+)<\/a>/i', $xml, $match)) {
            return strtolower($match[1]);
        }
        return null;
    }
}
