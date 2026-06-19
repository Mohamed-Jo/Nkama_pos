<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payments;
use App\Models\Shift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 🔎 TURNO ACTUAL
    |--------------------------------------------------------------------------
    */
    public function currentShift()
    {
        $operatorId = session('operator_id');

        if (!$operatorId) {
            return response()->json(['open' => false]);
        }

        $shift = Shift::where('operator_id', $operatorId)
            ->where('status', 'open')
            ->latest()
            ->first();

        return response()->json([
            'open' => $shift ? true : false,
            'shift' => $shift
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🟢 ABRIR TURNO
    |--------------------------------------------------------------------------
    */
    public function openShift(Request $request)
    {
        $operatorId = session('operator_id');

        if (!$operatorId) {
            return response()->json([
                'success' => false,
                'message' => 'Operador não autenticado'
            ], 401);
        }

        $request->validate([
            'opening_cash' => 'required|numeric|min:0'
        ]);

        $existingShift = Shift::where('operator_id', $operatorId)
            ->where('status', 'open')
            ->latest()
            ->first();

        if ($existingShift) {
            return response()->json([
                'success' => true,
                'already_open' => true,
                'message' => 'Já existe um caixa aberto para este operador',
                'shift' => $existingShift
            ]);
        }

        $shift = Shift::create([
            'operator_id'  => $operatorId,
            'opening_cash' => $request->opening_cash,
            'status'       => 'open',
            'opened_at'    => now(),
        ]);

        return response()->json([
            'success'      => true,
            'already_open' => false,
            'message'      => 'Caixa aberto com sucesso',
            'shift'        => $shift
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 💰 RESUMO FINANCEIRO (PREVISÃO)
    |--------------------------------------------------------------------------
    */
    public function summary()
    {
        $operatorId = session('operator_id');

        $shift = Shift::where('operator_id', $operatorId)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'success'  => false,
                'expected' => 0
            ]);
        }

        $cashTotal   = Payments::where('shift_id', $shift->id)->where('method', 'cash')->sum('amount');
        $cardTotal   = Payments::where('shift_id', $shift->id)->where('method', 'card')->sum('amount');
        $multiTotal  = Payments::where('shift_id', $shift->id)->where('method', 'multi')->sum('amount');
        $transfTotal = Payments::where('shift_id', $shift->id)->where('method', 'transf')->sum('amount');

        // O total esperado em dinheiro físico na gaveta é a abertura + vendas em dinheiro
        $expectedCashPhysical = $shift->opening_cash + $cashTotal;
        $totalSystemSales     = $cashTotal + $cardTotal + $multiTotal + $transfTotal;

        return response()->json([
            'success'             => true,
            'shift_id'            => $shift->id,
            'opening_cash'        => $shift->opening_cash,
            'cash_sales_total'    => $cashTotal,
            'card_sales_total'    => $cardTotal,
            'multi_sales_total'   => $multiTotal,
            'transf_sales_total'  => $transfTotal,
            'expected'            => $expectedCashPhysical, // Foco no dinheiro físico
            'total_sales'         => $totalSystemSales
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔴 FECHAR TURNO (COM CORRECÇÃO DE AUDITORIA)
    |--------------------------------------------------------------------------
    */
    public function closeShift(Request $request)
    {
        $operatorId = session('operator_id');

        $request->validate([
            'counted_cash' => 'required|numeric|min:0'
        ]);

        $shift = Shift::where('operator_id', $operatorId)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum caixa aberto'
            ], 404);
        }

        $cashTotal   = Payments::where('shift_id', $shift->id)->where('method', 'cash')->sum('amount');
        $cardTotal   = Payments::where('shift_id', $shift->id)->where('method', 'card')->sum('amount');
        $multiTotal  = Payments::where('shift_id', $shift->id)->where('method', 'multi')->sum('amount');
        $transfTotal = Payments::where('shift_id', $shift->id)->where('method', 'transf')->sum('amount');

        // CORRECÇÃO FINANCEIRA: O operador só conta dinheiro vivo. O esperado é Abertura + Vendas Dinheiro.
        $expectedCashPhysical = $shift->opening_cash + $cashTotal;
        $countedCash          = (float) $request->counted_cash;
        $difference           = $countedCash - $expectedCashPhysical;

        $shift->update([
            'status'             => 'closed',
            'closed_at'          => now(),
            'cash_sales_total'   => $cashTotal,
            'card_sales_total'   => $cardTotal,
            'multi_sales_total'  => $multiTotal,
            'transf_sales_total' => $transfTotal,
            'expected_cash'      => $expectedCashPhysical,
            'closing_cash'       => $countedCash,
            'difference'         => $difference
        ]);

        return response()->json([
            'success'            => true,
            'message'            => 'Caixa fechado com sucesso',
            'shift_id'           => $shift->id,
            'expected_cash'      => $expectedCashPhysical,
            'counted_cash'       => $countedCash,
            'difference'         => $difference,
            'cash_sales_total'   => $cashTotal,
            'card_sales_total'   => $cardTotal,
            'multi_sales_total'  => $multiTotal,
            'transf_sales_total' => $transfTotal
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 📜 HISTÓRICO DE CAIXAS (MÉTODOS ADICIONADOS)
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        // Procura os caixas fechados e pagina de 15 em 15
        $shifts = Shift::where('status', 'closed')
            ->orderBy('closed_at', 'desc')
            ->paginate(15);

        return view('admin.shifts.history', compact('shifts'));
    }

    public function show($id)
    {
        // Busca um caixa específico para auditoria detalhada
        $shift = Shift::findOrFail($id);

        // Procura todos os pagamentos associados a este turno específico
        $payments = Payments::where('shift_id', $shift->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.shifts.show', compact('shift', 'payments'));
    }
}