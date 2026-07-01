<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CreditNote;
use App\Models\CurrentAccountEntry;
use App\Models\Payments;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\AuditLogger;
use App\Services\BusinessSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemDateController extends Controller
{
    public function next(): RedirectResponse
    {
        $currentDate = Cache::get('system_date', now()->toDateString());
        $date = Carbon::parse($currentDate);
        $reportPath = $this->generateDailyReport($date);
        $nextDate = $date->copy()->addDay()->toDateString();

        Cache::forever('system_date', $nextDate);

        AuditLogger::log('system_date_advanced', 'SystemDate', null, [
            'before' => $currentDate,
            'after' => $nextDate,
            'daily_report' => $reportPath,
        ]);

        return back()
            ->with('success', 'Relatorio diario gerado e data do sistema alterada para ' . Carbon::parse($nextDate)->format('d/m/Y'))
            ->with('daily_report_url', route('admin.system-date.daily-report', ['date' => $date->toDateString()]));
    }

    public function downloadDailyReport(string $date): BinaryFileResponse
    {
        $day = Carbon::parse($date)->toDateString();
        $path = $this->dailyReportPath($day);

        abort_unless(Storage::disk('local')->exists($path), 404, 'Relatorio diario nao encontrado.');

        return response()->download(
            Storage::disk('local')->path($path),
            'lancamento-diario-' . $day . '.pdf'
        );
    }

    private function generateDailyReport(Carbon $date): string
    {
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $sales = Sale::with('customer', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $creditNotes = CreditNote::with('customer', 'operator', 'originalSale')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $payments = Payments::with('sale', 'creditNote', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $cashMovements = CashMovement::with('operator', 'currentAccountEntry')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $currentAccountEntries = CurrentAccountEntry::with('operator')
            ->whereBetween('entry_date', [$date->toDateString(), $date->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $purchases = Purchase::with('supplier', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $shifts = Shift::with('operator')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('opened_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->orderBy('opened_at')
            ->get();

        $company = BusinessSettings::company();
        $path = $this->dailyReportPath($date->toDateString());

        $pdf = Pdf::loadView('admin.reports.daily-postings', [
            'title' => 'Relatorio de Lancamento Diario',
            'company' => $company,
            'logoUrl' => BusinessSettings::logoDataUri($company),
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
            'sales' => $sales,
            'creditNotes' => $creditNotes,
            'payments' => $payments,
            'cashMovements' => $cashMovements,
            'currentAccountEntries' => $currentAccountEntries,
            'purchases' => $purchases,
            'shifts' => $shifts,
            'totals' => [
                'sales' => (float) $sales->sum('total'),
                'credit_notes' => (float) $creditNotes->sum('total'),
                'payments' => (float) $payments->sum('amount'),
                'cash_movements' => (float) $cashMovements->sum('amount'),
                'account_debit' => (float) $currentAccountEntries->sum('debit'),
                'account_credit' => (float) $currentAccountEntries->sum('credit'),
                'purchases' => (float) $purchases->sum('total'),
            ],
        ])->setPaper('a4', 'portrait');

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    private function dailyReportPath(string $date): string
    {
        return 'reports/daily/lancamento-diario-' . $date . '.pdf';
    }
}
