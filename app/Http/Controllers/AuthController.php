<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login']]);
    // }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }
    public function register(Request $request){
        try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tenantDbName = 'tenant_' . $user->id;
        Tenant::create([
            'user_id'  => $user->id,
            'database' => $tenantDbName,
        ]);

        // إنشاء قاعدة بيانات جديدة للمستأجر
        DB::statement("CREATE DATABASE {$tenantDbName}");

        // إعداد الاتصال بقاعدة بيانات الـ Tenant
        config(['database.connections.tenant.database' => $tenantDbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // تشغيل ميجريشن الـ Tenant لإنشاء جدول الملاحظات
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => '/database/migrations/tenant',
            '--force'    => true,
        ]);

        // إدراج سجل افتراضي في جدول الملاحظات يحتوي على اسم المستخدم
        DB::connection('tenant')->table('notes')->insert([
            'content'    => 'مرحبا ' . $user->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
      
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'حدث خطأ',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}