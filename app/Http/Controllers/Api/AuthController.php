<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ─── Helper: generate & store OTP ────────────────────────────────────────
    private function generateOtp(string $email, string $type): string
    {
        // Invalidate old OTPs for this email+type
        DB::table('otp_codes')
            ->where('email', $email)
            ->where('type', $type)
            ->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('otp_codes')->insert([
            'email'      => $email,
            'code'       => $otp,
            'type'       => $type,
            'used'       => false,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $otp;
    }

    // ─── Helper: verify OTP ───────────────────────────────────────────────────
    private function verifyOtp(string $email, string $code, string $type): bool
    {
        $record = DB::table('otp_codes')
            ->where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) return false;

        DB::table('otp_codes')
            ->where('id', $record->id)
            ->update(['used' => true]);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1: Send registration OTP
    // ─────────────────────────────────────────────────────────────────────────
    public function sendRegisterOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'name'  => 'required|string|max:255',
        ]);

        $otp = $this->generateOtp($request->email, 'registration');

        Mail::to($request->email)
            ->send(new OtpMail($otp, 'registration', $request->name));

        return response()->json([
            'message' => 'OTP sent to ' . $request->email,
            'email'   => $request->email,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2: Verify OTP + complete registration
    // ─────────────────────────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:8',
            'otp'             => 'required|string|size:6',
            'phone'           => 'nullable|string|max:20',
            'education_level' => 'nullable|string',
            'country'         => 'nullable|string|max:100',
        ]);

        if (!$this->verifyOtp($request->email, $request->otp, 'registration')) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP. Please request a new one.'],
            ]);
        }

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'education_level' => $request->education_level,
            'country'         => $request->country,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('scholarhub_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGIN
    // ─────────────────────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        $token = $user->createToken('scholarhub_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORGOT PASSWORD: Send OTP
    // ─────────────────────────────────────────────────────────────────────────
    public function sendForgotOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp  = $this->generateOtp($request->email, 'forgot_password');

        Mail::to($request->email)
            ->send(new OtpMail($otp, 'forgot_password', $user->name));

        return response()->json([
            'message' => 'Password reset OTP sent to ' . $request->email,
            'email'   => $request->email,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORGOT PASSWORD: Verify OTP + reset password
    // ─────────────────────────────────────────────────────────────────────────
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!$this->verifyOtp($request->email, $request->otp, 'forgot_password')) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password reset successfully. You can now log in.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET PROFILE
    // ─────────────────────────────────────────────────────────────────────────
    public function me(Request $request)
    {
        return response()->json($request->user()->load('applications'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE PROFILE
    // ─────────────────────────────────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name'            => 'sometimes|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'education_level' => 'nullable|string',
            'field_of_study'  => 'nullable|string|max:255',
            'country'         => 'nullable|string|max:100',
        ]);

        $user->update($request->only(['name','phone','education_level','field_of_study','country']));

        return response()->json($user->fresh());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
}
