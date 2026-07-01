<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operator;
use App\Services\AuditLogger;
use App\Services\BusinessSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * LOGIN SCREEN
     */
    public function kiosk()
    {
        if (session()->has('operator_id')) {
            return redirect()->route('admin.pos.index');
        }

        $company = BusinessSettings::company();

        return view('kiosk', [
            'loginBackgroundUrl' => BusinessSettings::loginBackgroundUrl($company),
        ]);
    }

    /**
     * LOGIN PIN (OFFLINE POS)
     */
    public function auth(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:8'
        ]);

        $pinFingerprint = Operator::pinFingerprint($request->pin);
        $operator = Operator::where('active', 1)
            ->where('pin_fingerprint', $pinFingerprint)
            ->first();

        if (!$operator) {
            $operator = Operator::where('active', 1)
                ->whereNull('pin_fingerprint')
                ->get()
                ->first(fn (Operator $operator) => Hash::check($request->pin, $operator->pin));

            if ($operator && !Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($operator->id)->exists()) {
                $operator->forceFill(['pin_fingerprint' => $pinFingerprint])->save();
            }
        }

        if (!$operator) {
            AuditLogger::log('login_failed', 'Operator', null, [
                'reason' => 'invalid_pin',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PIN inválido'
            ], 401);
        }

        session([
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'operator_role' => $operator->role,
        ]);

        AuditLogger::log('login', 'Operator', $operator->id, [
            'operator_name' => $operator->name,
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
        AuditLogger::log('logout', 'Operator', session('operator_id'), [
            'operator_name' => session('operator_name'),
        ]);

        session()->forget([
            'operator_id',
            'operator_name',
            'operator_role'
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/kiosk');
    }
}
