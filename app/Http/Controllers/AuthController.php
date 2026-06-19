<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operator;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * LOGIN SCREEN
     */
    public function kiosk()
    {
        if (session()->has('operator_id')) {
            return redirect('/pos');
        }

        return view('kiosk');
    }

    /**
     * LOGIN PIN (OFFLINE POS)
     */
    public function auth(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:8'
        ]);

        $operator = Operator::where('pin', $request->pin)
            ->where('active', 1)
            ->first();

        if (!$operator) {
            return response()->json([
                'success' => false,
                'message' => 'PIN inválido'
            ], 401);
        }

        session([
            'operator_id' => $operator->id,
            'operator_name' => $operator->name
        ]);

        return response()->json([
            'success' => true,
            'operator' => $operator
        ]);
    }

    /**
     * LOGOUT POS
     */
    public function logout(Request $request)
    {
        session()->forget([
            'operator_id',
            'operator_name'
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/kiosk');
    }
}