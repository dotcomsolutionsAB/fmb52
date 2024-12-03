<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RazorpayService;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'jamiat_id' => 'required|integer',
            'thaali_count' => 'required|integer|min:1',
        ]);

        $jamiatId = $request->input('jamiat_id');
        $thaaliCount = $request->input('thaali_count');

        // Calculate the amount based on thaali count
        $amountPerThaali = $thaaliCount <= 250 ? 72 : ($thaaliCount <= 500 ? 60 : 53);
        $amount = $thaaliCount * $amountPerThaali;

        try {
            // Create Razorpay order
            $razorpayOrder = $this->razorpayService->createOrder($amount, 'INR');

            // Save order in the database
            $order = Order::create([
                'jamiat_id' => $jamiatId,
                'thaali_count' => $thaaliCount,
                'payment_date' => null,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => $order,
                'razorpay_order' => $razorpayOrder,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }
}