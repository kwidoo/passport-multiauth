<?php

namespace Kwidoo\MultiAuth\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Kwidoo\MultiAuth\Contracts\UserResolver;

class OTPController
{
    /**
     */
    public function __construct(protected array $otpGenerators, protected UserResolver $resolver) {}

    public function create(Request $request)
    {
        $request->validate([
            'method'   => 'required|string',
            'username' => 'required|string',
        ]);

        if (!array_key_exists($request->method, $this->otpGenerators)) {
            return response()->json(['message' => 'Unsupported OTP method'], 422);
        }

        $generator = $this->otpGenerators[$request->method];

        try {
            $result = $generator->create($request->username);

            // If OTP was not sent (user has a password)
            if ($result === false) {
                return response()->json(['message' => 'OTP not sent, user has a password set'], 200);
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'OTP was sent successfully'])
                : view('passport-multiauth::otp.sent');
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
