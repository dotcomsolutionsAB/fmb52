<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RazorpayService
{
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id');
        $this->keySecret = config('services.razorpay.key_secret');
    }

    public function createOrder($amount, $currency = 'INR', $receipt = null)
    {
        $url = 'https://api.razorpay.com/v1/orders';

        $data = [
            'amount' => $amount * 100, // Convert amount to paise
            'currency' => $currency,
            'receipt' => $receipt ?? uniqid('receipt_'),
        ];

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->post($url, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to create Razorpay order: ' . $response->body());
        }

        return $response->json();
    }
}