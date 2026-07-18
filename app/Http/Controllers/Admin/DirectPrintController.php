<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\CashMovement;
use App\Models\Payments;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\BusinessSettings;
use App\Services\DirectPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DirectPrintController extends Controller
{
    public function sale(Sale $sale, DirectPrintService $printer): JsonResponse
    {
        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissao para imprimir esta venda.');
        }

        $sale->load('operator', 'items.product', 'payments', 'customer.card', 'customerCard.balanceTransactions', 'pointTransactions');
        $company = BusinessSettings::company();
        $printSettings = BusinessSettings::print();
        $invoiceSettings = BusinessSettings::invoice();
        $agtQrImage = (bool) ($invoiceSettings['show_agt_qr'] ?? true)
            ? BusinessSettings::agtQrImage($company, $sale->invoice_number, 88)
            : null;

        return $this->send(function () use ($printer, $sale, $company, $printSettings, $invoiceSettings, $agtQrImage) {
            $printer->printView('admin.sales.ticket', [
                'sale' => $sale,
                'company' => $company,
                'logoUrl' => BusinessSettings::logoUrl($company),
                'printSettings' => $printSettings,
                'agtQrImage' => $agtQrImage,
            ], 'ticket-' . $sale->invoice_number . '.pdf');
        });
    }

    public function creditNote(CreditNote $creditNote, DirectPrintService $printer): JsonResponse
    {
        $creditNote->load('originalSale', 'customer', 'operator', 'items.product', 'payments');
        $company = BusinessSettings::company();

        return $this->send(function () use ($printer, $creditNote, $company) {
            $printer->printView('admin.credit-notes.ticket', [
                'creditNote' => $creditNote,
                'company' => $company,
                'logoUrl' => BusinessSettings::logoUrl($company),
                'printSettings' => BusinessSettings::print(),
            ], 'nc-' . $creditNote->invoice_number . '.pdf');
        });
    }

    public function table(RestaurantTable $table, DirectPrintService $printer): JsonResponse
    {
        $table->load('currentOrder.items.product');
        $order = $table->currentOrder;

        if (! $order || in_array($order->status, ['closed', 'cancelled', 'canceled'], true)) {
            abort(404, 'Mesa sem conta aberta.');
        }

        $company = BusinessSettings::company();

        return $this->send(function () use ($printer, $table, $order, $company) {
            $printer->printView('admin.restaurant.table-ticket', [
                'table' => $table,
                'order' => $order,
                'company' => $company,
                'logoUrl' => BusinessSettings::logoUrl($company),
                'printSettings' => BusinessSettings::print(),
                'totals' => $this->restaurantOrderTotals($order),
            ], 'consulta-' . $table->name . '.pdf');
        });
    }

    public function shift(Shift $shift, DirectPrintService $printer): JsonResponse|RedirectResponse
    {
        $shift->load('operator');
        $payments = Payments::where('shift_id', $shift->id)->orderBy('created_at')->get();
        $cashMovements = CashMovement::where('shift_id', $shift->id)->orderBy('created_at')->get();
        $company = BusinessSettings::company();

        return $this->send(function () use ($printer, $shift, $payments, $cashMovements, $company) {
            $printer->printView('admin.shifts.ticket', [
                'shift' => $shift,
                'payments' => $payments,
                'cashMovements' => $cashMovements,
                'company' => $company,
                'logoUrl' => BusinessSettings::logoUrl($company),
                'printSettings' => BusinessSettings::print(),
            ], 'fecho-caixa-' . $shift->id . '.pdf');
        });
    }

    private function send(callable $callback): JsonResponse|RedirectResponse
    {
        try {
            $callback();

            if (! request()->expectsJson()) {
                return back()->with('success', 'Documento enviado para a impressora.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento enviado para a impressora.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            if (! request()->expectsJson()) {
                return back()->with('error', $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function restaurantOrderTotals($order): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $total = 0.0;
        $taxBreakdown = [];

        foreach ($order->items as $item) {
            $lineTotal = (float) $item->subtotal;
            $taxRate = (float) ($item->product?->tax_rate ?? 0);
            $split = BusinessSettings::splitGrossTotal($lineTotal, $taxRate);

            $subtotal += $split['subtotal'];
            $tax += $split['tax'];
            $total += $split['total'];

            $key = number_format($taxRate, 2, '.', '');
            $taxBreakdown[$key] ??= [
                'rate' => $taxRate,
                'incidence' => 0.0,
                'tax' => 0.0,
            ];
            $taxBreakdown[$key]['incidence'] += $split['subtotal'];
            $taxBreakdown[$key]['tax'] += $split['tax'];
        }

        ksort($taxBreakdown, SORT_NUMERIC);

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'tax_breakdown' => array_map(fn ($row) => [
                'rate' => round($row['rate'], 2),
                'incidence' => round($row['incidence'], 2),
                'tax' => round($row['tax'], 2),
            ], array_values($taxBreakdown)),
        ];
    }
}
