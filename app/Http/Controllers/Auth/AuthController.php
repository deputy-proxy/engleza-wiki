<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller {
    public function showLogin() { return view('pages.auth.login'); }
    public function login(Request $r) {
        $data = $r->validate(['email'=>'required|email','password'=>'required']);
        if (Auth::attempt($data, $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
        return back()->withErrors(['email'=>'Invalid credentials'])->onlyInput('email');
    }
    public function showRegister() { return view('pages.auth.register'); }
    public function register(Request $r) {
        $data = $r->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>['required','confirmed', Password::min(8)],
        ]);
        $u = User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>bcrypt($data['password']),'role'=>'learner']);
        Auth::login($u);
        return redirect()->route('dashboard');
    }
    public function logout(Request $r) {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/');
    }
}
