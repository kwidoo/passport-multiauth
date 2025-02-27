<?php

namespace Kwidoo\MultiAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OTPController extends Controller
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

        $generator = $this->otpGenerators[$request->method]
            ?? throw new InvalidArgumentException("Unsupported OTP method");
        try {
            $generator->create($request->username);

            return $request->expectsJson() ? response()->json(['message' => 'OTP was sent successfully']) : view('passport-multiauth::otp.sent');
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
