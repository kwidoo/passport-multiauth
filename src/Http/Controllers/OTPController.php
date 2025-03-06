<?php

namespace Kwidoo\MultiAuth\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class OTPController
{
    /**
     * @param OTPServiceInterface[] $otpGenerators
     */
    public function __construct(protected array $otpGenerators) {}

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
            $generator->create($request->username);

            return $request->expectsJson() ? response()->json(['message' => 'OTP was sent successfully']) : view('passport-multiauth::otp.sent');
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
