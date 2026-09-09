<?php

namespace App\Services;

use App\Models\AccountingAccount;
use App\Models\AccountingJournalEntry;
use App\Models\CashMovement;
use App\Models\CreditNote;
use App\Models\CurrentAccountEntry;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use Carbon\Carbon;

class AccountingPostingService
{
    private const ACCOUNT_CASH = '11';
    private const ACCOUNT_BANK = '12';
    private const ACCOUNT_CARD_CLEARING = '13';
    private const ACCOUNT_CUSTOMERS = '21';
    private const ACCOUNT_CUSTOMER_CARDS = '22';
    private const ACCOUNT_SUPPLIERS = '31';
    private const ACCOUNT_TAX_PAYABLE = '34';
    private const ACCOUNT_TAX_DEDUCTIBLE = '35';
    private const ACCOUNT_SALES = '41';
    private const ACCOUNT_INVENTORY = '48';
    private const ACCOUNT_PURCHASES = '51';
    private const ACCOUNT_COGS = '61';
    private const ACCOUNT_EXPENSES = '62';
    private const ACCOUNT_EQUITY = '81';

    public function ensureStandardAccounts(): void
    {
        foreach ($this->standardAccounts() as $code => $account) {
            AccountingAccount::firstOrCreate(['code' => $code], $account);
        }
    }

    public function postSale(Sale $sale): ?AccountingJournalEntry
    {
        if ((bool) ($sale->is_proforma ?? false)) {
            return null;
        }

        $sale->loadMissing('payments', 'items.product');
        $paid = round(max((float) $sale->payments->sum('amount'), (float) ($sale->paid ?? 0)), 2);
        $outstanding = round(max((float) $sale->total - $paid, 0), 2);
        $debits = [];

        foreach ($this->paymentDebits($sale->payments->pluck('amount', 'method')->all()) as $accountCode => $amount) {
            $this->add($debits, $accountCode, $amount);
        }
        $this->add($debits, self::ACCOUNT_CUSTOMERS, $outstanding);

        $cost = $this->inventoryCost($sale->items);
        $this->add($debits, self::ACCOUNT_COGS, $cost);
        $credits = $this->salesCredits((float) $sale->subtotal, (float) $sale->tax);
        $this->add($credits, self::ACCOUNT_INVENTORY, $cost);

        return $this->post(
            'sale',
            (int) $sale->id,
            $sale->created_at ?: now(),
            $sale->invoice_number,
            'Venda ' . $sale->invoice_number,
            $debits,
            $credits,
            (int) ($sale->operator_id ?: session('operator_id'))
        );
    }

    public function postCreditNote(CreditNote $creditNote): ?AccountingJournalEntry
    {
        $creditNote->loadMissing('payments', 'items.product');

        $refunds = $creditNote->payments
            ->filter(fn ($payment) => (float) $payment->amount < 0)
            ->mapWithKeys(fn ($payment) => [$payment->method => abs((float) $payment->amount)])
            ->all();

        $accountCredit = (float) CurrentAccountEntry::where('document_type', 'credit_note')
            ->where('document_id', $creditNote->id)
            ->sum('credit');

        $credits = [];
        $this->add($credits, self::ACCOUNT_CUSTOMERS, $accountCredit);
        foreach ($this->paymentDebits($refunds) as $accountCode => $amount) {
            $this->add($credits, $accountCode, $amount);
        }

        $cost = $this->inventoryCost($creditNote->items);
        $debits = $this->salesCredits((float) $creditNote->subtotal, (float) $creditNote->tax);
        $this->add($debits, self::ACCOUNT_INVENTORY, $cost);
        $this->add($credits, self::ACCOUNT_COGS, $cost);

        return $this->post(
            'credit_note',
            (int) $creditNote->id,
            $creditNote->created_at ?: now(),
            $creditNote->invoice_number,
            'Nota de credito ' . $creditNote->invoice_number,
            $debits,
            $credits,
            (int) ($creditNote->operator_id ?: session('operator_id'))
        );
    }

    public function postPurchaseApproval(Purchase $purchase): ?AccountingJournalEntry
    {
        $date = $purchase->purchase_date ?: now();
        $credits = [];
        $creditAccount = $purchase->payment_type === 'credit' ? self::ACCOUNT_SUPPLIERS : self::ACCOUNT_CASH;
        $this->add($credits, $creditAccount, (float) $purchase->total);

        return $this->post(
            'purchase',
            (int) $purchase->id,
            $date,
            $purchase->supplier_invoice_number ?: $purchase->document_number,
            'Compra #' . $purchase->id,
            $this->purchaseDebits((float) $purchase->subtotal + (float) $purchase->expenses_total, (float) $purchase->tax),
            $credits,
            (int) ($purchase->approved_by ?: session('operator_id'))
        );
    }

    public function postPurchaseReturn(PurchaseReturn $return): ?AccountingJournalEntry
    {
        return $this->post(
            'purchase_return',
            (int) $return->id,
            $return->return_date ?: now(),
            $return->document_number,
            'Devolucao fornecedor #' . $return->purchase_id,
            [self::ACCOUNT_SUPPLIERS => (float) $return->total],
            [self::ACCOUNT_INVENTORY => (float) $return->total],
            (int) ($return->operator_id ?: session('operator_id'))
        );
    }

    public function postExpense(Expense $expense): ?AccountingJournalEntry
    {
        $creditAccount = match ($expense->status) {
            Expense::STATUS_PENDING => self::ACCOUNT_SUPPLIERS,
            default => $expense->bank_account_id ? self::ACCOUNT_BANK : self::ACCOUNT_CASH,
        };

        return $this->post(
            'expense',
            (int) $expense->id,
            $expense->expense_date ?: now(),
            $expense->document_number,
            'Despesa: ' . $expense->description,
            [self::ACCOUNT_EXPENSES => (float) $expense->amount],
            [$creditAccount => (float) $expense->amount],
            (int) ($expense->operator_id ?: session('operator_id'))
        );
    }

    public function postCurrentAccountSettlement(CurrentAccountEntry $entry): ?AccountingJournalEntry
    {
        $isCustomerReceipt = $entry->entity_type === 'customer' && (float) $entry->credit > 0;
        $isSupplierPayment = $entry->entity_type === 'supplier' && (float) $entry->debit > 0;

        if (! $isCustomerReceipt && ! $isSupplierPayment) {
            return null;
        }

        $amount = $isCustomerReceipt ? (float) $entry->credit : (float) $entry->debit;
        $cashMovement = CashMovement::where('current_account_entry_id', $entry->id)->first();
        $cashOrBank = in_array($cashMovement?->method, ['card', 'transf', 'bank'], true) ? self::ACCOUNT_BANK : self::ACCOUNT_CASH;

        return $this->post(
            'current_account_entry',
            (int) $entry->id,
            $entry->entry_date ?: now(),
            null,
            $entry->description ?: 'Liquidacao de conta corrente',
            $isCustomerReceipt ? [$cashOrBank => $amount] : [self::ACCOUNT_SUPPLIERS => $amount],
            $isCustomerReceipt ? [self::ACCOUNT_CUSTOMERS => $amount] : [$cashOrBank => $amount],
            (int) ($entry->operator_id ?: session('operator_id'))
        );
    }

    public function postBankTransaction(int $transactionId, string $type, float $amount, string $date, ?string $reference, ?string $description, ?int $operatorId = null): ?AccountingJournalEntry
    {
        return $this->post(
            'bank_transaction',
            $transactionId,
            $date,
            $reference,
            $description ?: 'Movimento bancario',
            $type === 'credit' ? [self::ACCOUNT_BANK => $amount] : [self::ACCOUNT_EQUITY => $amount],
            $type === 'credit' ? [self::ACCOUNT_EQUITY => $amount] : [self::ACCOUNT_BANK => $amount],
            (int) ($operatorId ?: session('operator_id'))
        );
    }

    private function post(string $sourceType, int $sourceId, Carbon|string $date, ?string $documentNumber, string $description, array $debits, array $credits, ?int $operatorId): ?AccountingJournalEntry
    {
        if (AccountingJournalEntry::where('source_type', $sourceType)->where('source_id', $sourceId)->exists()) {
            return null;
        }

        $debits = $this->clean($debits);
        $credits = $this->clean($credits);
        $totalDebit = round(array_sum($debits), 2);
        $totalCredit = round(array_sum($credits), 2);

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.001) {
            throw new \RuntimeException('Lancamento contabilistico automatico desbalanceado para ' . $sourceType . ' #' . $sourceId . '.');
        }

        $entry = AccountingJournalEntry::create([
            'entry_date' => Carbon::parse($date)->toDateString(),
            'document_number' => $documentNumber,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'status' => 'posted',
            'posted_by' => $operatorId ?: null,
        ]);

        foreach ($debits as $accountCode => $amount) {
            $entry->lines()->create([
                'accounting_account_id' => $this->account($accountCode)->id,
                'debit' => $amount,
                'credit' => 0,
            ]);
        }

        foreach ($credits as $accountCode => $amount) {
            $entry->lines()->create([
                'accounting_account_id' => $this->account($accountCode)->id,
                'debit' => 0,
                'credit' => $amount,
            ]);
        }

        return $entry;
    }

    private function salesCredits(float $subtotal, float $tax): array
    {
        $credits = [self::ACCOUNT_SALES => $subtotal];
        $this->add($credits, self::ACCOUNT_TAX_PAYABLE, $tax);

        return $credits;
    }

    private function purchaseDebits(float $subtotal, float $tax): array
    {
        $debits = [self::ACCOUNT_INVENTORY => $subtotal];
        $this->add($debits, self::ACCOUNT_TAX_DEDUCTIBLE, $tax);

        return $debits;
    }

    private function paymentDebits(array $payments): array
    {
        $debits = [];

        foreach ($payments as $method => $amount) {
            $account = match ($method) {
                'card', 'multi' => self::ACCOUNT_CARD_CLEARING,
                'transf', 'bank' => self::ACCOUNT_BANK,
                'customer_card' => self::ACCOUNT_CUSTOMER_CARDS,
                default => self::ACCOUNT_CASH,
            };
            $this->add($debits, $account, (float) $amount);
        }

        return $debits;
    }
    private function inventoryCost($items): float
    {
        return round((float) $items->sum(function ($item) {
            $product = $item->product;

            if (! $product || ! ($product->track_stock ?? true)) {
                return 0;
            }

            return (float) $item->quantity * (float) $product->purchase_price;
        }), 2);
    }

    private function add(array &$lines, string $accountCode, float $amount): void
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return;
        }

        $lines[$accountCode] = round((float) ($lines[$accountCode] ?? 0) + $amount, 2);
    }

    private function clean(array $lines): array
    {
        return collect($lines)
            ->map(fn ($amount) => round((float) $amount, 2))
            ->filter(fn ($amount) => $amount > 0)
            ->all();
    }

    private function account(string $code): AccountingAccount
    {
        return AccountingAccount::firstOrCreate(
            ['code' => $code],
            $this->standardAccounts()[$code] ?? [
                'name' => 'Conta ' . $code,
                'type' => 'asset',
                'active' => true,
            ]
        );
    }

    private function standardAccounts(): array
    {
        return [
            self::ACCOUNT_CASH => ['name' => 'Caixa', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_BANK => ['name' => 'Bancos/Transferencias', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_CARD_CLEARING => ['name' => 'TPA/Cartoes a receber', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_CUSTOMERS => ['name' => 'Clientes', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_CUSTOMER_CARDS => ['name' => 'Cartoes de cliente/fidelidade', 'type' => 'liability', 'active' => true],
            self::ACCOUNT_SUPPLIERS => ['name' => 'Fornecedores', 'type' => 'liability', 'active' => true],
            self::ACCOUNT_TAX_PAYABLE => ['name' => 'IVA liquidado', 'type' => 'liability', 'active' => true],
            self::ACCOUNT_TAX_DEDUCTIBLE => ['name' => 'IVA dedutivel', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_SALES => ['name' => 'Vendas', 'type' => 'income', 'active' => true],
            self::ACCOUNT_INVENTORY => ['name' => 'Inventario/Mercadorias', 'type' => 'asset', 'active' => true],
            self::ACCOUNT_PURCHASES => ['name' => 'Compras/Mercadorias', 'type' => 'expense', 'active' => true],
            self::ACCOUNT_COGS => ['name' => 'Custo das mercadorias vendidas', 'type' => 'expense', 'active' => true],
            self::ACCOUNT_EXPENSES => ['name' => 'Gastos gerais', 'type' => 'expense', 'active' => true],
            self::ACCOUNT_EQUITY => ['name' => 'Capital/Resultados', 'type' => 'equity', 'active' => true],
        ];
    }
}
