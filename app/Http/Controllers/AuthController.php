<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    /**
     * Admin credentials (hardcoded for simple auth).
     */
    private const ADMIN_EMAIL = 'tanin@admin.com';
    private const ADMIN_PASSWORD = 'Ta19NinAdmin';

    /**
     * Validate admin login credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $isValid = $email === self::ADMIN_EMAIL && $password === self::ADMIN_PASSWORD;

        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? 'Login successful.' : 'Invalid credentials.',
        ]);
    }
}

