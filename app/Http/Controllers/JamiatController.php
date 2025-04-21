<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JamiatModel;
use App\Models\User;
use App\Models\JamiatSettingsModel;
use App\Models\SuperAdminReceiptsModel;
use App\Models\SuperAdminCounterModel;
use App\Services\MailService;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class JamiatController extends Controller
{

    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function verify_email(Request $request)
    {
        $recipientEmail = $request->input('email');
        $code = rand(100000, 999999); // Generates a random 6-digit number

        $existsInTjamiat = DB::table('t_jamiat')->where('email', $request->email)->exists();
        $existsInUsers = DB::table('users')->where('email', $request->email)->exists();
    
        if ($existsInTjamiat || $existsInUsers) {
            return response()->json([
                'status' => false,
                'message' => 'The email is already taken.',
            ], 400);
        }

        $subject = 'Verify Your Email Address';
        $body = '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    margin: 0;
                    padding: 0;
                    background-color: #f7f7f7;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .header h1 {
                    font-size: 24px;
                    color: #4CAF50;
                }
                .content {
                    font-size: 16px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 16px;
                    margin-top: 20px;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #777777;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1>Email Verification</h1>
                </div>
                <div class="content">
                    <p>Thank you for signing up! To complete your registration, please verify your email by using the code below:</p>
                    <p>Code : '.$code.'</p>
                    <p>If you did not sign up for this account, please ignore this email.</p>
                </div>
                <div class="footer">
                    <p>Thank you,<br>FMB 52 Team</p>
                </div>
            </div>
        </body>
        </html>
        ';

        $response = $this->mailService->sendMail($recipientEmail, $subject, $body);

        return response()->json(['status' => true, 'message' => $response, 'code' => $code]);
    }

    // create
    public function register_jamaat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'admin_name' => 'required|string|max:150',
            'mobile' => 'required|string|max:20',
            'email' => 'required|string|email|max:150',
            'currency_id' => 'required|exists:currencies,id', // Validate currency_id
        ]);

        $existsInTjamiat = DB::table('t_jamiat')->where('email', $request->email)->exists();
        $existsInUsers = DB::table('users')->where('email', $request->email)->exists();

        if ($existsInTjamiat || $existsInUsers) {
            return response()->json([
                'status' => false,
                'message' => 'The email is already taken.',
            ], 200);
        }

        try {
            // Generate a random 6-digit password
            $password = random_int(100000, 999999);

            // Create Jamiat with required fields and currency_id
            $jamiat = JamiatModel::create([
                'name' => $request->input('name'),
                'mobile' => $request->input('mobile'),
                'email' => $request->input('email'),
                'currency_id' => $request->input('currency_id'), // Save currency_id
                'package' => 0,
                'validity' => now()->addDays(30)->format('Y-m-d'),
                'billing_address' => null,
                'billing_contact' => null,
                'billing_email' => null,
                'billing_phone' => null,
                'last_payment_date' => null,
                'last_payment_amount' => null,
                'payment_due_date' => null,
                'notes' => null,
                'logs' => null,
            ]);

            if (!$jamiat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to register Jamaat. Please try again!',
                ], 500);
            }

            // Insert new year into t_year table
            DB::transaction(function () use ($jamiat) {
                // Set all existing years for the Jamaat to is_current = 0
                DB::table('t_year')->where('jamiat_id', $jamiat->id)->update(['is_current' => 0]);

                // Insert the new year with is_current = 1
                DB::table('t_year')->insert([
                    'year' => '1446-1447',
                    'jamiat_id' => $jamiat->id,
                    'is_current' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            // Create a new user associated with the created Jamiat
            $register_user = User::create([
                'name' => strtolower($request->input('admin_name')),
                'email' => strtolower($request->input('email')),
                'password' => bcrypt($password),
                'jamiat_id' => $jamiat->id,
                'family_id' => null,
                'its' => null,
                'hof_its' => null,
                'its_family_id' => null,
                'mobile' => $request->input('mobile'),
                'title' => null,
                'gender' => null,
                'age' => null,
                'building' => null,
                'folio_no' => null,
                'sector_id' => null,
                'sub_sector_id' => null,
                'role' => 'jamiat_admin',
                'status' => 'active',
                'username' => strtolower($request->input('email')),
            ]);

            // Assign all permissions to the user
            $allPermissions = Permission::where('guard_name', 'sanctum')->get();
            $register_user->givePermissionTo($allPermissions);

            // Send email to the new user
            try {
                $recipientEmail = $register_user->email;
                $subject = 'Welcome to FMB 52!';
                $body = view('emails.jamaat_registration', [
                    'admin_name' => $register_user->email,
                    'password' => $password,
                    'validity' => $jamiat->validity,
                ])->render();

                $result = app('mailService')->sendMail($recipientEmail, $subject, $body);

                if (!$result) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to send email. Please try again later.',
                    ], 500);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Jamaat registered successfully, and email sent!',
                    'data' => [
                        'jamiat' => $jamiat,
                        'user' => $register_user,
                    ],
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'An error occurred while sending the email.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Some error occurred!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function forgot_password(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        // Check if user exists by username
        $user = User::where('username', $request->input('username'))->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid username',
            ], 200);
        }

        try {
            // Generate a new 6-digit password
            $newPassword = random_int(100000, 999999);

            // Update the user's password in the database
            $user->password = bcrypt($newPassword);
            $user->save();

            // Prepare email details
            $recipientEmail = $user->email;
            $subject = 'Password Reset Notification';
            $body = view('emails.forgot_password', [
                'name' => $user->name,
                'new_password' => $newPassword,
            ])->render();

            // Send email synchronously
            $result = app('mailService')->sendMail($recipientEmail, $subject, $body);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send email. Please try again later.',
                ], 500);
            }

            return response()->json([
                'status' => true,
                'message' => 'Password reset successfully. Check your ' . $recipientEmail . 'for the new password.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while resetting the password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // view
    public function view_jamiats()
    {
        $jamiats = JamiatModel::select('id','name', 'status', 'validity', 'mobile', 'email', 'rate', 'billing_address', 'billing_contact', 'billing_email', 'billing_phone', 'last_payment_date', 'last_payment_amount', 'payment_due_date', 'notes', 'logs')->get();

        return $jamiats->isNotEmpty()
            ? response()->json(['message' => 'Jamiats fetched successfully!', 'data' => $jamiats], 200)
            : response()->json(['message' => 'No Jamiats found!'], 404);
    }

    // update
    public function update_jamiat(Request $request, $id)
    {
        $jamiat = JamiatModel::find($id);

        if (!$jamiat) {
            return response()->json(['message' => 'Jamiat not found!'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'required|string|max:20',
            'email' => 'required|string|email|max:150|unique:t_jamiat,email,' . $id,
            'package' => 'required|integer',
            'billing_address' => 'nullable|string',
            'billing_contact' => 'nullable|string|max:150',
            'billing_email' => 'nullable|string|email|max:150',
            'billing_phone' => 'nullable|string|max:20',
            'last_payment_date' => 'nullable|date',
            'last_payment_amount' => 'nullable|numeric',
            'payment_due_date' => 'nullable|date',
            'validity' => 'required|date',
            'notes' => 'nullable|string',
            'logs' => 'nullable|string',
        ]);

        $update_jamiat = $jamiat->update([
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'package' => $request->input('package'),
            'billing_address' => $request->input('billing_address'),
            'billing_contact' => $request->input('billing_contact'),
            'billing_email' => $request->input('billing_email'),
            'billing_phone' => $request->input('billing_phone'),
            'last_payment_date' => $request->input('last_payment_date'),
            'last_payment_amount' => $request->input('last_payment_amount'),
            'payment_due_date' => $request->input('payment_due_date'),
            'validity' => $request->input('validity'),
            'notes' => $request->input('notes'),
            'logs' => $request->input('logs')
        ]);

        return ($update_jamiat == 1)
            ? response()->json(['message' => 'Jamiat updated successfully!', 'data' => $update_jamiat], 200)
            : response()->json(['No changes detected'], 304);
    }
    // delete
    public function delete_jamiat($id)
    {
        // Find the Jamiat by ID
        $jamiat = JamiatModel::find($id);
    
        // Check if the Jamiat exists
        if (!$jamiat) {
            return response()->json(['message' => 'Jamiat not found!'], 404);
        }
    
        // Delete all users associated with this Jamiatgit commit
        $jamiatId = $jamiat->id;
    
        // Using the DB facade to delete users
        \DB::table('users')->where('jamiat_id', $jamiatId)->delete();
    
        // Delete the Jamiat
        $jamiat->delete();
    
        return response()->json(['message' => 'Jamiat and associated users deleted successfully!'], 200);
    }


    // create
    public function register_jamiat_settings(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'value' => 'required|string'
        ]);

        $jamiat_setting = JamiatSettingsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'value' => $request->input('value')
        ]);

        unset($jamiat_setting['id'], $jamiat_setting['created_at'], $jamiat_setting['updated_at']);

        return isset($jamiat_setting)
            ? response()->json(['message' => 'Setting created successfully!', 'data' => $jamiat_setting], 201)
            : response()->json(['message' => 'Failed to create setting!'], 400);
    }

    // view
    public function view_jamiat_settings()
    {
        $settings = JamiatSettingsModel::select('jamiat_id', 'name', 'value')->get();

        return $settings->isNotEmpty()
            ? response()->json(['message' => 'Settings fetched successfully!', 'data' => $settings], 200)
            : response()->json(['message' => 'No settings found!'], 404);
    }

    // update
    public function update_jamiat_settings(Request $request, $id)
    {
        $jamiat_settings = JamiatSettingsModel::find($id);

        if (!$jamiat_settings) {
            return response()->json(['message' => 'Setting not found!'], 404);
        }

        $request->validate([
            'jamiat_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'value' => 'required|string'
        ]);

        $update_jamiat_settings = $jamiat_settings->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'name' => $request->input('name'),
            'value' => $request->input('value')
        ]);

        return ($update_jamiat_settings == 1)
            ? response()->json(['message' => 'Jamiat settings updated successfully!', 'data' => $update_jamiat_settings], 200)
            : response()->json(['No changes detected'], 304);

    }

    // delete
    public function delete_jamiat_settings($id)
    {
        $jamiat_setting = JamiatSettingsModel::find($id);

        if (!$jamiat_setting) {
            return response()->json(['message' => 'Setting not found!'], 404);
        }

        $jamiat_setting->delete();

        return response()->json(['message' => 'Setting deleted successfully!'], 200);
    }

    // create
    public function register_super_admin_receipts(Request $request)
    {
        // Validation rules for the receipt
        $request->validate([
            'jamiat_id' => 'required|integer',
            'amount' => 'required|numeric',
            'package' => 'required|integer',
            'payment_date' => 'required|date',
            'receipt_number' => 'required|string|max:100|unique:t_super_admin_receipts,receipt_number',
            'created_by' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        // Create new receipt record
        $receipt = SuperAdminReceiptsModel::create([
            'jamiat_id' => $request->input('jamiat_id'),
            'amount' => $request->input('amount'),
            'package' => $request->input('package'),
            'payment_date' => $request->input('payment_date'),
            'receipt_number' => $request->input('receipt_number'),
            'created_by' => $request->input('created_by'),
            'notes' => $request->input('notes')
        ]);

        unset($receipt['id'], $receipt['created_at'], $receipt['updated_at']);

        return isset($receipt)
            ? response()->json(['message' => 'Receipt created successfully!', 'data' => $receipt], 201)
            : response()->json(['message' => 'Failed to create receipt!'], 400);
    }

    // view
    public function view_super_admin_receipts()
    {
        // Fetch all receipt records
        $receipts = SuperAdminReceiptsModel::select('jamiat_id', 'amount', 'package', 'payment_date', 'receipt_number', 'created_by', 'notes')->get();

        return $receipts->isNotEmpty()
            ? response()->json(['message' => 'Receipts fetched successfully!', 'data' => $receipts], 200)
            : response()->json(['message' => 'No receipts found!'], 404);
    }

    // update
    public function update_super_admin_receipt(Request $request, $id)
    {
        // Find the specific receipt by ID
        $receipt = SuperAdminReceiptsModel::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Receipt not found!'], 404);
        }

        // Validation rules for updating the receipt
        $request->validate([
            'jamiat_id' => 'required|integer',
            'amount' => 'required|numeric',
            'package' => 'required|integer',
            'payment_date' => 'required|date',
            'receipt_number' => 'required|string|max:100|unique:t_super_admin_receipts,receipt_number,' . $id,
            'created_by' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        // Update the receipt record
        $update_receipt = $receipt->update([
            'jamiat_id' => $request->input('jamiat_id'),
            'amount' => $request->input('amount'),
            'package' => $request->input('package'),
            'payment_date' => $request->input('payment_date'),
            'receipt_number' => $request->input('receipt_number'),
            'created_by' => $request->input('created_by'),
            'notes' => $request->input('notes')
        ]);

        return ($update_receipt == 1)
            ? response()->json(['message' => 'Receipt updated successfully!', 'data' => $update_receipt], 200)
            : response()->json(['No changes detected'], 304);
    }

    public function delete_super_admin_receipt($id)
    {
        // Find the specific receipt by ID
        $receipt = SuperAdminReceiptsModel::find($id);

        if (!$receipt) {
            return response()->json(['message' => 'Receipt not found!'], 404);
        }

        // Delete the receipt
        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted successfully!'], 200);
    }

    // create
    public function register_super_admin_counter(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100|unique:t_super_admin_counter,key',
            'value' => 'required|string',
        ]);

        $counter_entry = SuperAdminCounterModel::create([
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ]);

        unset($counter_entry['id'], $counter_entry['created_at'], $counter_entry['updated_at']);

        return isset($counter_entry)
            ? response()->json(['message' => 'Counter entry created successfully!', 'data' => $counter_entry], 201)
            : response()->json(['message' => 'Failed to create counter entry!'], 400);
    }

    // view
    public function view_super_admin_counters()
    {
        $counters = SuperAdminCounterModel::select('key', 'value')->get();

        return $counters->isNotEmpty()
            ? response()->json(['message' => 'Counters fetched successfully!', 'data' => $counters], 200)
            : response()->json(['message' => 'No counter entries found!'], 404);
    }

    // update
    public function update_super_admin_counter(Request $request, $id)
    {
        $counter_entry = SuperAdminCounterModel::find($id);

        if (!$counter_entry) {
            return response()->json(['message' => 'Counter entry not found!'], 404);
        }

        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $update_counter_entry = $counter_entry->update([
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ]);

        return ($update_counter_entry == 1)
            ? response()->json(['message' => 'Super-Admin counter updated successfully!', 'data' => $update_counter_entry], 200)
            : response()->json(['No changes detected'], 304);
    }

    // delete
    public function delete_super_admin_counter($id)
    {
        // $counter_entry = SuperAdminCounterModel::where('key', $key)->first();
        // Find the specific receipt by ID
        $counter_entry = SuperAdminCounterModel::find($id);

        if (!$counter_entry) {
            return response()->json(['message' => 'Counter entry not found!'], 404);
        }

        $counter_entry->delete();

        return response()->json(['message' => 'Counter entry deleted successfully!'], 200);
    }
}
