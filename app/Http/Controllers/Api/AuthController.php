<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Verified;
use App\Models\Vendor;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    // ================= REGISTER =================

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'nullable|in:vendor,customer',
            'phone' => 'nullable|string',

            'firm_name' => 'required_if:role,vendor|string|max:255',
            'business_type' => 'required_if:role,vendor|string|max:255',
            'gst_number' => 'required_if:role,vendor|string|max:50|unique:vendors,gst_number',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'customer',
            'phone' => $request->phone,
        ]);

        if ($user->role === 'vendor') {
            Vendor::create([
                'user_id' => $user->id,
                'firm_name' => $request->firm_name,
                'business_type' => $request->business_type,
                'gst_number' => $request->gst_number,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registered successfully. Please verify your email.',
        ], 201);
    }

    // ================= LOGIN =================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email first.'], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // ================= LOGOUT =================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // ================= PROFILE =================
    public function profile(Request $request)
    {
        $user = $request->user();

        // If vendor, load vendor relationship
        if ($user->role === 'vendor') {
            $user->load('vendor');
        }

        return response()->json([
            'user' => $user
        ]);
    }

    // ================= UPDATE PROFILE =================

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email:rfc',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string',

            // Vendor fields (only required if vendor)
            'firm_name' => 'required_if:role,vendor|string|max:255',
            'business_type' => 'required_if:role,vendor|string|max:255',
            'gst_number' => [
                'required_if:role,vendor',
                Rule::unique('vendors', 'gst_number')->ignore(optional($user->vendor)->id),
            ],
        ]);

        $this->blockTempEmail($request->email);

        // ================= EMAIL CHANGE =================
        if ($request->email !== $user->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $user->save();

            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Email changed. Please verify your new email.',
                'require_verification' => true,
            ]);
        }

        // ================= UPDATE USER =================
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        // ================= UPDATE VENDOR =================
        if ($user->role === 'vendor') {

            $vendor = $user->vendor;

            if ($vendor) {
                $vendor->update([
                    'firm_name' => $request->firm_name,
                    'business_type' => $request->business_type,
                    'gst_number' => $request->gst_number,
                ]);
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('vendor'),
        ]);
    }
    // ================= VERIFY EMAIL =================
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully']);
    }

    // ================= RESEND EMAIL =================
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent again']);
    }

    // ================= HELPER =================
    private function blockTempEmail($email)
    {
        $blocked = ['mailinator.com', 'tempmail.com', '10minutemail.com'];
        $domain = substr(strrchr($email, "@"), 1);

        if (in_array($domain, $blocked)) {
            abort(422, 'Temporary email addresses are not allowed.');
        }
    }
    // ================= FORGOT PASSWORD =================
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check user exists first to give a helpful message
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email address.',
            ], 404);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to send reset link. Please try again.',
        ], 500);
    }

    // ================= RESET PASSWORD =================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully. Please login with your new password.',
            ]);
        }

        // Map Laravel's error status to a readable message
        $errorMessage = match ($status) {
            Password::INVALID_TOKEN => 'This reset link is invalid or has already been used.',
            Password::INVALID_USER  => 'No account found with this email address.',
            Password::RESET_THROTTLED => 'Too many attempts. Please wait before trying again.',
            default                 => 'Failed to reset password. Please try again.',
        };

        return response()->json(['message' => $errorMessage], 422);
    }
}
