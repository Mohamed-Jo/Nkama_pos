<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Payments;
use App\Models\Shift;
use App\Services\BusinessSettings;
use App\Services\DirectPrintService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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

        $payload = [
            'operator_id'  => $operatorId,
            'user_id'      => auth()->id(),
            'opening_cash' => $request->opening_cash,
            'status'       => 'open',
            'opened_at'    => now(),
        ];

        $payload = collect($payload)
            ->filter(fn ($value, $column) => $value !== null && Schema::hasColumn('shifts', $column))
            ->all();

        $shift = Shift::create($payload);

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

        $cashTotal   = $this->methodTotal($shift->id, 'cash');
        $cardTotal   = $this->methodTotal($shift->id, 'card');
        $multiTotal  = $this->methodTotal($shift->id, 'multi');
        $transfTotal = $this->methodTotal($shift->id, 'transf');

        // O total esperado em dinheiro físico na gaveta é a abertura + vendas em dinheiro
        $expectedCashPhysical = $shift->opening_cash + $cashTotal;
        $totalSystemSales     = $cashTotal + $cardTotal + $multiTotal + $transfTotal;
        $salesCount           = $this->salesCount($shift->id);

        return response()->json([
            'success'             => true,
            'shift_id'            => $shift->id,
            'opening_cash'        => $shift->opening_cash,
            'cash_sales_total'    => $cashTotal,
            'card_sales_total'    => $cardTotal,
            'multi_sales_total'   => $multiTotal,
            'transf_sales_total'  => $transfTotal,
            'expected'            => $expectedCashPhysical, // Foco no dinheiro físico
            'total_sales'         => $totalSystemSales,
            'sales_count'         => $salesCount
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔴 FECHAR TURNO (COM CORRECÇÃO DE AUDITORIA)
    |--------------------------------------------------------------------------
    */
    public function closeShift(Request $request, DirectPrintService $printer)
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

        $cashTotal   = $this->methodTotal($shift->id, 'cash');
        $cardTotal   = $this->methodTotal($shift->id, 'card');
        $multiTotal  = $this->methodTotal($shift->id, 'multi');
        $transfTotal = $this->methodTotal($shift->id, 'transf');

        // CORRECÇÃO FINANCEIRA: O operador só conta dinheiro vivo. O esperado é Abertura + Vendas Dinheiro.
        $expectedCashPhysical = $shift->opening_cash + $cashTotal;
        $countedCash          = (float) $request->counted_cash;
        $difference           = $countedCash - $expectedCashPhysical;
        $totalSystemSales     = $cashTotal + $cardTotal + $multiTotal + $transfTotal;
        $salesCount           = $this->salesCount($shift->id);

        $payload = [
            'status'             => 'closed',
            'closed_at'          => now(),
            'cash_sales_total'   => $cashTotal,
            'card_sales_total'   => $cardTotal,
            'multi_sales_total'  => $multiTotal,
            'transf_sales_total' => $transfTotal,
            'expected_cash'      => $expectedCashPhysical,
            'closing_cash'       => $countedCash,
            'difference'         => $difference,
            'total_sales'        => $totalSystemSales,
            'sales_count'        => $salesCount
        ];

        if (Schema::hasColumn('shifts', 'notes')) {
            $payload['notes'] = $request->input('notes');
        }

        $shift->update($payload);
        $shift->refresh();

        $printResult = $this->printShiftSummary($shift, $printer);

        return response()->json([
            'success'            => true,
            'message'            => $printResult['success']
                ? 'Caixa fechado com sucesso. Resumo enviado para impressao.'
                : 'Caixa fechado com sucesso, mas o resumo nao foi impresso: ' . $printResult['message'],
            'printed'            => $printResult['success'],
            'print_message'      => $printResult['message'],
            'shift_id'           => $shift->id,
            'expected_cash'      => $expectedCashPhysical,
            'counted_cash'       => $countedCash,
            'difference'         => $difference,
            'cash_sales_total'   => $cashTotal,
            'card_sales_total'   => $cardTotal,
            'multi_sales_total'  => $multiTotal,
            'transf_sales_total' => $transfTotal,
            'total_sales'        => $totalSystemSales,
            'sales_count'        => $salesCount
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

        $cashMovements = CashMovement::where('shift_id', $shift->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.shifts.show', compact('shift', 'payments', 'cashMovements'));
    }

    private function methodTotal(int $shiftId, string $method): float
    {
        return (float) Payments::where('shift_id', $shiftId)->where('method', $method)->sum('amount')
            + (float) CashMovement::where('shift_id', $shiftId)->where('method', $method)->sum('amount');
    }

    private function salesCount(int $shiftId): int
    {
        return Payments::where('shift_id', $shiftId)
            ->whereNotNull('sale_id')
            ->distinct('sale_id')
            ->count('sale_id');
    }

    private function printShiftSummary(Shift $shift, DirectPrintService $printer): array
    {
        try {
            $shift->load('operator');
            $payments = Payments::where('shift_id', $shift->id)->orderBy('created_at')->get();
            $cashMovements = CashMovement::where('shift_id', $shift->id)->orderBy('created_at')->get();
            $company = BusinessSettings::company();

            $printer->printView('admin.shifts.ticket', [
                'shift' => $shift,
                'payments' => $payments,
                'cashMovements' => $cashMovements,
                'company' => $company,
                'logoUrl' => BusinessSettings::logoUrl($company),
                'printSettings' => BusinessSettings::print(),
            ], 'fecho-caixa-' . $shift->id . '.pdf');

            return ['success' => true, 'message' => 'Resumo enviado para impressao.'];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
