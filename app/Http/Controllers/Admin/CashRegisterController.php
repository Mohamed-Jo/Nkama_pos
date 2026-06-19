<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cash_Register;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function open(Request $request)
    {
        return Cash_Register::create([
            'user_id' => auth()->id(),
            'opening_amount' => $request->amount,
            'status' => 'open',
            'opened_at' => now()
        ]);
    }

    public function close($id)
    {
        $cash = Cash_Register::findOrFail($id);

        $cash->update([
            'closing_amount' => request('closing_amount'),
            'status' => 'closed',
            'closed_at' => now()
        ]);

        return response()->json($cash);
    }
}

