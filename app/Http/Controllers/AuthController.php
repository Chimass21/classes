<?php

namespace App\Http\Controllers;

use App\Helpers\JsonDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin()
    {
        return redirect()->route('landing');
    }

    public function showRegister()
    {
        return redirect()->route('landing');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        JsonDb::init();
        $loginField = $credentials['login'];
        $user = null;
        if (filter_var($loginField, FILTER_VALIDATE_EMAIL)) {
            $user = JsonDb::findUserByEmail($loginField);
        } else {
            $user = JsonDb::findUserByUsername($loginField);
        }
        if (!$user) {
            $user = JsonDb::findUserByLogin($loginField);
        }
        
        if ($user && $this->checkPassword($credentials['password'], $user)) {
            Session::put('user', $user);
            if ($user['role'] === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user['role'] === 'teacher') {
                return redirect()->route('teacher.dashboard');
            } else {
                return redirect()->route('student.dashboard');
            }
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function showAdminLogin()
    {
        $user = Session::get('user');
        if ($user && $user['role'] === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        \App\Http\Controllers\AdminController::ensureAdminExists();
        return view('admin.login');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        JsonDb::init();
        \App\Http\Controllers\AdminController::ensureAdminExists();
        $db = JsonDb::get();

        $user = JsonDb::findUserByLogin($credentials['login']);

        if ($user && $this->checkPassword($credentials['password'], $user)) {
            if ($user['role'] !== 'admin') {
                return back()->withErrors(['login' => 'Access denied. Admin credentials required.'])->onlyInput('login');
            }
            Session::put('user', $user);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'login' => 'Invalid admin credentials.',
        ])->onlyInput('login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,teacher,admin',
        ]);

        JsonDb::init();
        $db = JsonDb::get();

        $existing = JsonDb::findUserByEmail($request->email);
        if ($existing) {
            return back()->withErrors(['email' => 'An account with this email already exists.'])->onlyInput('email');
        }

        $id = 'usr_' . uniqid();
        $hashedPassword = Hash::make($request->password);
        $newUser = [
            'id' => $id,
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username ?? null,
            'password' => $hashedPassword,
            'role' => $request->role,
            'walletBalance' => 0,
            'isSuspended' => false,
            'createdAt' => now()->toIso8601String(),
        ];
        if ($request->role === 'student') {
            $newUser['regNumber'] = 'REG/' . date('Y') . '/' . rand(1000, 9999);
        }
        $db['users'][] = $newUser;

        try {
            JsonDb::createUser($newUser);
        } catch (\Exception $e) {}
        JsonDb::save($db);
        Session::put('user', $newUser);

        if ($newUser['role'] === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($newUser['role'] === 'teacher') {
            return redirect()->route('teacher.dashboard');
        } else {
            return redirect()->route('student.dashboard');
        }
    }

    public function logout(Request $request)
    {
        Session::forget('user');
        $request->session()->invalidate();
        return redirect()->route('landing');
    }

    public function apiLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        JsonDb::init();
        $loginField = $request->login;
        $user = JsonDb::findUserByLogin($loginField);

        if ($user && $this->checkPassword($request->password, $user)) {
            Session::put('user', $user);
            return response()->json(['success' => true, 'user' => $user]);
        }

        return response()->json(['success' => false, 'error' => 'Invalid credentials.'], 401);
    }

    public function apiRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:student,teacher',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        JsonDb::init();
        $db = JsonDb::get();

        $existing = JsonDb::findUserByEmail($request->email);
        if ($existing) {
            return response()->json(['success' => false, 'error' => 'An account with this email already exists.'], 409);
        }

        $id = 'usr_' . uniqid();
        $hashedPassword = Hash::make($request->password);
        $newUser = [
            'id' => $id,
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username ?? null,
            'password' => $hashedPassword,
            'role' => $request->role,
            'walletBalance' => 0,
            'isSuspended' => false,
            'createdAt' => now()->toIso8601String(),
        ];
        if ($request->role === 'student') {
            $newUser['regNumber'] = 'REG/' . date('Y') . '/' . rand(1000, 9999);
        }
        $db['users'][] = $newUser;

        try {
            JsonDb::createUser($newUser);
        } catch (\Exception $e) {}
        JsonDb::save($db);
        Session::put('user', $newUser);

        return response()->json(['success' => true, 'user' => $newUser]);
    }

    public function apiSession()
    {
        $user = Session::get('user');
        if ($user) {
            return response()->json(['user' => $user]);
        }
        return response()->json(['user' => null]);
    }

    public function apiLogout()
    {
        Session::forget('user');
        return response()->json(['success' => true]);
    }

    public function apiReset(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Verification instruction sent!']);
    }

    public function switchToStudent()
    {
        $user = Session::get('user');
        if (!$user) return redirect()->route('login');

        Session::put('original_user', $user);
        $switched = $user;
        $switched['role'] = 'student';
        $switched['_switched'] = true;
        $switched['_original_role'] = $user['role'];
        Session::put('user', $switched);

        return redirect()->route('student.dashboard');
    }

    public function switchToTeacher()
    {
        $user = Session::get('user');
        if (!$user) return redirect()->route('login');

        Session::put('original_user', $user);
        $switched = $user;
        $switched['role'] = 'teacher';
        $switched['_switched'] = true;
        $switched['_original_role'] = $user['role'];
        Session::put('user', $switched);

        return redirect()->route('teacher.dashboard');
    }

    public function switchBack()
    {
        $original = Session::get('original_user');
        if ($original) {
            Session::put('user', $original);
            Session::forget('original_user');
            return redirect()->route($original['role'] . '.dashboard');
        }
        return redirect()->route('landing');
    }

    private function checkPassword($plaintext, &$user)
    {
        if (Hash::check($plaintext, $user['password'])) {
            return true;
        }
        if ($user['password'] === $plaintext) {
            $user['password'] = Hash::make($plaintext);
            $db = JsonDb::get();
            foreach ($db['users'] as &$u) {
                if ($u['id'] === $user['id']) {
                    $u['password'] = $user['password'];
                    break;
                }
            }
            JsonDb::updateUserPassword($user['id'], $user['password']);
            JsonDb::save($db);
            return true;
        }
        return false;
    }
}
