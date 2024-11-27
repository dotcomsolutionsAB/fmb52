<?php

namespace App\Http\Controllers;

class TestMiddlewareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function test()
    {
        return response()->json(['message' => 'Middleware test passed!']);
    }
}
