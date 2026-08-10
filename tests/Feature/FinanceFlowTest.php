<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\Operator;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_dashboard_can_be_rendered(): void
    {
        $operator = $this->operator();

        $this->withSession(['operator_id' => $operator->id])
            ->get('/admin/finance')
            ->assertOk()
            ->assertSee('Financeiro');
    }
    public function test_bank_account_and_manual_transaction_update_balance(): void
    {
        $operator = $this->operator();

        $this->withSession(['operator_id' => $operator->id])
            ->post('/admin/finance/bank-accounts', [
                'name' => 'BAI Operacional',
                'bank_name' => 'BAI',
                'currency' => 'AOA',
                'opening_balance' => 1000,
            ])
            ->assertRedirect();

        $account = BankAccount::firstOrFail();
        $this->assertSame('1000.00', (string) $account->current_balance);

        $this->withSession(['operator_id' => $operator->id])
            ->post('/admin/finance/bank-transactions', [
                'bank_account_id' => $account->id,
                'type' => 'credit',
                'amount' => 500,
                'transaction_date' => now()->toDateString(),
                'reference' => 'DEP-1',
                'description' => 'Deposito teste',
            ])
            ->assertRedirect();

        $account->refresh();
        $transaction = BankTransaction::firstOrFail();

        $this->assertSame('1500.00', (string) $account->current_balance);
        $this->assertSame('1000.00', (string) $transaction->balance_before);
        $this->assertSame('1500.00', (string) $transaction->balance_after);
    }

    public function test_bank_transaction_can_be_reconciled(): void
    {
        $operator = $this->operator();
        $account = BankAccount::create([
            'name' => 'Conta QA',
            'currency' => 'AOA',
            'opening_balance' => 0,
            'current_balance' => 0,
            'active' => true,
        ]);
        $transaction = BankTransaction::create([
            'bank_account_id' => $account->id,
            'operator_id' => $operator->id,
            'type' => 'credit',
            'method' => 'manual',
            'amount' => 100,
            'balance_before' => 0,
            'balance_after' => 100,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->withSession(['operator_id' => $operator->id])
            ->patch("/admin/finance/bank-transactions/{$transaction->id}/reconcile")
            ->assertRedirect();

        $transaction->refresh();
        $this->assertTrue($transaction->reconciled);
        $this->assertSame($operator->id, (int) $transaction->reconciled_by);
    }

    public function test_cash_expense_creates_negative_cash_movement(): void
    {
        $operator = $this->operator();
        $shift = Shift::create([
            'operator_id' => $operator->id,
            'opening_cash' => 1000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->withSession(['operator_id' => $operator->id])
            ->post('/admin/finance/expenses', [
                'category' => 'Energia',
                'description' => 'Pagamento energia',
                'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'amount' => 250,
            ])
            ->assertRedirect();

        $expense = Expense::firstOrFail();
        $this->assertSame(Expense::STATUS_PAID, $expense->status);
        $this->assertSame($shift->id, (int) $expense->shift_id);
        $this->assertSame(1, CashMovement::where('type', 'expense')->where('amount', -250)->count());
    }

    public function test_bank_expense_creates_bank_debit_transaction(): void
    {
        $operator = $this->operator();
        $account = BankAccount::create([
            'name' => 'Banco Despesas',
            'currency' => 'AOA',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'active' => true,
        ]);

        $this->withSession(['operator_id' => $operator->id])
            ->post('/admin/finance/expenses', [
                'category' => 'Renda',
                'description' => 'Renda loja',
                'expense_date' => now()->toDateString(),
                'payment_method' => 'bank',
                'bank_account_id' => $account->id,
                'amount' => 300,
            ])
            ->assertRedirect();

        $account->refresh();
        $expense = Expense::firstOrFail();
        $transaction = BankTransaction::firstOrFail();

        $this->assertSame('700.00', (string) $account->current_balance);
        $this->assertSame($expense->id, (int) $transaction->expense_id);
        $this->assertSame('debit', $transaction->type);
        $this->assertSame('700.00', (string) $transaction->balance_after);
    }

    private function operator(): Operator
    {
        $pin = str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);

        return Operator::create([
            'name' => 'Gestor Financeiro',
            'pin' => $pin,
            'pin_fingerprint' => Operator::pinFingerprint($pin),
            'role' => 'admin',
            'active' => true,
        ]);
    }
}