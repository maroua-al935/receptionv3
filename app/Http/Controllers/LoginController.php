<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;
use App\Models\user_groups as ug;
use App\Models\User;

class LoginController extends Controller
{
    public function index()
    {
        return view('login');
    }
    public function login(Request $request)
    {
        $valid = $request->validate([
           'name'=>['required','bail'],
           'password'=>['required','bail']
        ]);
        $creds = [
           'samaccountname'=>$valid['name'],
           'password'=>$valid['password']
        ];

        if ($this->attemptLocalLogin($valid['name'], $valid['password'], true) || $this->attemptLdapLogin($creds)) {
            $user = Auth::guard('web')->user();
            $hasservice = ug::where('a_user', '=', $user->id)->count();
            $profile = (int) $user->profile;
            if (in_array($profile, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], true)) {
                return redirect()->route('home');
            } else {
                $msg="Vous n'êtes pas autorisé à utiliser cette application, veuillez ouvrir un ticket de support ou contacter le service informatique.";
                return $this->logout($request)->withErrors(['failed'=>$msg]);
            }
        } else {
            $msg="échec de l'authentification, nom d'utilisateur/mot de passe incorrect";
            return redirect()->back()->withErrors(['failed'=>$msg]);
        }
    }

    private function attemptLdapLogin(array $creds): bool
    {
        try {
            return Auth::attempt($creds, true);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function attemptLocalLogin(string $name, string $password, bool $remember = false): bool
    {
        $user = User::where('name', $name)
            ->orWhere('username', $name)
            ->orWhere('user_dn', $name)
            ->orWhere('email', $name)
            ->first();

        if (!$user || !$user->password || !Hash::check($password, $user->password)) {
            return false;
        }

        Auth::guard('web')->login($user, $remember);

        return true;
    }
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();



        $request->session()->regenerateToken();
        return redirect()->route('l_index');
    }
}
