<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpayOrderId = $request->input('razorpay_order_id');
        $razorpaySignature = $request->input('razorpay_signature');

        // Razorpay key secret
        $keySecret = 'tt7rx8jJtA0IxD5DawCOmCVL';

        // Verify the signature
        $generatedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . "|" . $razorpayPaymentId,
            $keySecret
        );

        if ($generatedSignature !== $razorpaySignature) {
            return response()->json(['error' => 'Invalid Razorpay signature'], 400);
        }

        // Update the order status
        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->update([
            'status' => 'completed',
            'payment_date' => now(),
            'razorpay_payment_id' => $razorpayPaymentId,
        ]);

        return response()->json([
            'message' => 'Payment verified and order updated successfully',
            'order' => $order,
        ], 200);
    }
    public function handleWebhook(Request $request)
    {
        // Get the payload from Razorpay
        $payload = $request->all();
        $razorpaySignature = $request->header('X-Razorpay-Signature');

        // Razorpay webhook secret
        $webhookSecret = '3nB$84pOuv@7ciiqcOKJ!nCJ^'; // Set this in Razorpay Dashboard

        // Verify the webhook signature
        $generatedSignature = hash_hmac(
            'sha256',
            $request->getContent(),
            $webhookSecret
        );

        if ($generatedSignature !== $razorpaySignature) {
            Log::error('Invalid webhook signature');
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        // Process the payment.captured event
        if (isset($payload['event']) && $payload['event'] === 'payment.captured') {
            $paymentId = $payload['payload']['payment']['entity']['id'];
            $orderId = $payload['payload']['payment']['entity']['order_id'];

            $order = Order::where('razorpay_order_id', $orderId)->first();

            if ($order) {
                $order->update([
                    'status' => 'completed',
                    'payment_date' => now(),
                    'razorpay_payment_id' => $paymentId,
                ]);

                return response()->json(['message' => 'Order updated successfully'], 200);
            } else {
                Log::error("Order not found for Razorpay order ID: {$orderId}");
                return response()->json(['error' => 'Order not found'], 404);
            }
        }

        return response()->json(['error' => 'Unhandled event'], 400);
    }
}