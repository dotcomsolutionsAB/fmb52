<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Utils\sendWhatsAppUtility;
use App\Models\User;

class AuthController extends Controller
{
    // genearate otp and send to `whatsapp`
    public function generate_otp(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
        ]);

        $username = $request->input('username');

        $get_user = User::select('id')
                        ->where('username', $username)
                        ->first();

        if(!$get_user == null)
        {
            $six_digit_otp = random_int(100000, 999999);

            $expiresAt = now()->addMinutes(10);

            $store_otp = User::where('username', $username)
                             ->update([
                                'otp' => $six_digit_otp,
                                'expires_at' => $expiresAt,
                            ]);

            if($store_otp)
            {
                $templateParams = [
                    'name' => 'ace_otp', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp,
                                ],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            "index" => "0",
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp,
                                ],
                            ],
                        ]
                    ],
                ];

                $whatsappUtility = new sendWhatsAppUtility();

                $response = $whatsappUtility->sendWhatsApp($get_user->mobile, $templateParams, $get_user->mobile, 'OTP Campaign');

                return response()->json([
                    'message' => 'Otp send successfully!',
                    'data' => $store_otp
                ], 200);
            }
        }
        else {
            return response()->json([
                'message' => 'User has not registered!',
            ], 404);
        }
    }

    // user `login`
    public function login(Request $request, $otp = null)
    {
        if($otp)
        {
            $request->validate([
                'username' => ['required', 'string'],
            ]);
            
            $otpRecord = User::select('otp', 'expires_at')
            ->where('username', $request->username)
            ->first();

            if($otpRecord)
            {
                // if(!$otpRecord || $otpRecord->otp != $otp)
                // {
                //     return response()->json(['message' => 'Sorry, invalid Otp!'], 400);
                // }
                // elseif ($otpRecord->expires_at < now()) {
                //     return response()->json(['message' => 'Sorry, otp has expired!'], 400);
                // }

                // else {
                    // Remove OTP record after successful validation
                    User::select('otp')->where('username', $request->username)->update(['otp' => null, 'expires_at' => null]);

                    // Retrieve the use
                    $user = User::where('username', $request->username)->first();

                    // Generate a sanctrum token
                    $generated_token = $user->createToken('API TOKEN')->plainTextToken;

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'token' => $generated_token,
                            'name' => $user->name,
                            'role' => $user->role,
                            'id' => $user->id,
                        ],
                        'message' => 'User logged in successfully!',
                    ], 200);
                // }
            }

            else {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not register.',
                    ], 401);
            }
        }

        else {
            $request->validate([
                'username' => ['required', 'string'],
                'password' => 'required',
            ]);

            // Find the user by username
            $user = User::where('username', $request->username)->first();

            if($user)
            // if(Auth::attempt(['username' => $request->username, 'password' => $request->password]))
            {
                // $user = Auth::user();

                // Generate a sanctrum token
                $generated_token = $user->createToken('API TOKEN')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $generated_token,
                        'name' => $user->name,
                        'role' => $user->role,
                        'id' => $user->id,
                    ],
                    'message' => 'User logged in successfully!',
                ], 200);
            }

            else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not register.',
                ], 401);
            }
        }
    }

    // user `logout`
    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if(!$request->user()) {
            return response()->json([
                'success'=> false,
                'message'=>'Sorry, no user is logged in now!',
            ], 401);
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully!',
        ], 204);
    }
}
