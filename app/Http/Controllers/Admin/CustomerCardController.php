<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCard;
use App\Models\CustomerCardAuthorizationRequest;
use App\Models\Operator;
use App\Models\PointTransaction;
use App\Services\AuditLogger;
use App\Services\CustomerCardAuthorizationService;
use App\Services\CustomerCardOtpService;
use App\Services\CustomerCardService;
use App\Services\ModuleSettings;
use App\Services\OperatorPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerCardController extends Controller
{
    public function index(): View
    {
        $cards = CustomerCard::with('customer')->latest()->paginate(20);

        return view('admin.customer-cards.index', [
            'cards' => $cards,
            'totalCards' => CustomerCard::count(),
            'activeCards' => CustomerCard::where('status', 'active')->count(),
            'blockedCards' => CustomerCard::where('status', 'blocked')->count(),
            'expiredCards' => CustomerCard::whereNotNull('expires_at')->whereDate('expires_at', '<', now()->toDateString())->count(),
            'pointsIssued' => PointTransaction::where('type', 'earn')->sum('points'),
            'pointsUsed' => abs(PointTransaction::where('type', 'redeem')->sum('points')),
            'topCards' => CustomerCard::with('customer')->orderByDesc('points')->take(5)->get(),
        ]);
    }


    public function authorizationsIndex(Request $request, CustomerCardAuthorizationService $authorizationService): View|JsonResponse
    {
        $authorizationService->expireOld();

        $status = $request->input('status', 'pending');
        $allowedStatuses = [
            CustomerCardAuthorizationService::STATUS_PENDING,
            CustomerCardAuthorizationService::STATUS_APPROVED,
            CustomerCardAuthorizationService::STATUS_REJECTED,
            CustomerCardAuthorizationService::STATUS_EXPIRED,
            CustomerCardAuthorizationService::STATUS_USED,
        ];

        $query = CustomerCardAuthorizationRequest::with('card.customer', 'requester', 'reviewer')
            ->latest();

        if (in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        $authorizations = $query->paginate(25)->withQueryString();
        $statusCounts = CustomerCardAuthorizationRequest::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'counts' => $statusCounts,
                'authorizations' => $authorizations->getCollection()
                    ->map(fn (CustomerCardAuthorizationRequest $authorization) => $this->authorizationPayload($authorization))
                    ->values(),
                'latest_id' => CustomerCardAuthorizationRequest::max('id'),
                'server_time' => now()->toDateTimeString(),
            ]);
        }

        return view('admin.customer-cards.authorizations', [
            'authorizations' => $authorizations,
            'status' => $status,
            'statusCounts' => $statusCounts,
        ]);
    }
    public function show(CustomerCard $customerCard): View
    {
        $customerCard->load('customer', 'transactions.sale', 'balanceTransactions.operator');

        return view('admin.customer-cards.show', [
            'card' => $customerCard,
            'customers' => Customer::where('status', true)->orderBy('name')->get(),
        ]);
    }

    public function lookup(Request $request, CustomerCardService $service): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:80'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $requestedAmount = round((float) ($validated['amount'] ?? 0), 2);
        $card = $service->lookup($validated['card_number']);

        if (!$card || !$service->isUsable($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Cartao nao encontrado, inativo ou expirado.',
            ], 404);
        }

        $capacity = $service->paymentCapacity($card);
        $availableForSale = $requestedAmount > 0
            ? min($requestedAmount, (float) $capacity['total_available'])
            : 0;

        return response()->json([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'customer_id' => $card->customer_id,
                'customer_name' => $card->customer?->name,
                'card_number' => $card->card_number,
                'level' => $card->level,
                'expires_at' => optional($card->expires_at)->toDateString(),
                'is_expired' => $card->is_expired,
                'available_for_sale' => round($availableForSale, 2),
                'has_enough_for_sale' => $requestedAmount > 0
                    ? ((float) $capacity['total_available'] + 0.0001 >= $requestedAmount)
                    : null,
            ],
        ]);
    }
    public function requestOtp(Request $request, CustomerCardService $cardService, CustomerCardOtpService $otpService): JsonResponse
    {
        if (!ModuleSettings::enabled('customer_card_otp')) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitacao de OTP desativada. Solicite autorizacao do gestor.',
            ], 403);
        }
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $card = $cardService->lookup((string) $validated['card_number']);

        if (!$card || !$cardService->isUsable($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Cartao nao encontrado, inativo ou expirado.',
            ], 404);
        }

        try {
            $otp = $otpService->issue($card, round((float) $validated['amount'], 2), session('operator_id'));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP enviado para o telefone do cliente.',
            'sent_to' => $otpService->maskPhone($otp->sent_to),
            'expires_at' => optional($otp->expires_at)->toDateTimeString(),
        ]);
    }
    public function requestAuthorization(Request $request, CustomerCardService $cardService, CustomerCardAuthorizationService $authorizationService): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:180'],
            'context' => ['nullable', 'array'],
        ]);

        $card = $cardService->lookup((string) $validated['card_number']);

        if (!$card || !$cardService->isUsable($card)) {
            return response()->json([
                'success' => false,
                'message' => 'Cartao nao encontrado, inativo ou expirado.',
            ], 404);
        }

        $authorization = $authorizationService->request(
            $card,
            round((float) $validated['amount'], 2),
            session('operator_id'),
            $validated['reason'] ?? null,
            $validated['context'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitacao enviada ao gestor.',
            'authorization' => $this->authorizationPayload($authorization->fresh(['card.customer', 'requester', 'reviewer'])),
        ]);
    }

    public function authorizationStatus(CustomerCardAuthorizationRequest $authorization, CustomerCardAuthorizationService $authorizationService): JsonResponse
    {
        $authorizationService->expireOld();
        $authorization->load('card.customer', 'requester', 'reviewer');

        if ((int) $authorization->requested_by_operator_id !== (int) session('operator_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitacao indisponivel para este operador.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'authorization' => $this->authorizationPayload($authorization),
        ]);
    }

    public function pendingAuthorizations(CustomerCardAuthorizationService $authorizationService): JsonResponse
    {
        $supervisor = Operator::find(session('operator_id'));

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Sessao expirada.'], 401);
        }

        try {
            $authorizationService->ensureSupervisor($supervisor);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        $authorizationService->expireOld();

        $authorizations = CustomerCardAuthorizationRequest::with('card.customer', 'requester', 'reviewer')
            ->where('status', CustomerCardAuthorizationService::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->take(20)
            ->get()
            ->map(fn (CustomerCardAuthorizationRequest $authorization) => $this->authorizationPayload($authorization))
            ->values();

        return response()->json([
            'success' => true,
            'authorizations' => $authorizations,
        ]);
    }

    public function approveAuthorization(Request $request, CustomerCardAuthorizationRequest $authorization, CustomerCardAuthorizationService $authorizationService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:180'],
        ]);

        $supervisor = Operator::find(session('operator_id'));

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Sessao expirada.'], 401);
        }

        try {
            [$authorization, $token] = $authorizationService->approve($authorization, $supervisor, $validated['note'] ?? null);
        } catch (\Throwable $e) {
            if (!$request->expectsJson()) {
                return back()->with('error', $e->getMessage());
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (!$request->expectsJson()) {
            return back()->with('success', 'Solicitacao aprovada.');
        }

        $payload = $this->authorizationPayload($authorization);
        $payload['token'] = $token;

        return response()->json([
            'success' => true,
            'message' => 'Solicitacao aprovada.',
            'authorization' => $payload,
        ]);
    }

    public function rejectAuthorization(Request $request, CustomerCardAuthorizationRequest $authorization, CustomerCardAuthorizationService $authorizationService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:180'],
        ]);

        $supervisor = Operator::find(session('operator_id'));

        if (!$supervisor) {
            return response()->json(['success' => false, 'message' => 'Sessao expirada.'], 401);
        }

        try {
            $authorization = $authorizationService->reject($authorization, $supervisor, $validated['note'] ?? null);
        } catch (\Throwable $e) {
            if (!$request->expectsJson()) {
                return back()->with('error', $e->getMessage());
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (!$request->expectsJson()) {
            return back()->with('success', 'Solicitacao rejeitada.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitacao rejeitada.',
            'authorization' => $this->authorizationPayload($authorization),
        ]);
    }
    public function updateDetails(Request $request, CustomerCard $customerCard): RedirectResponse
    {
        $validated = $request->validate(array_merge($this->supervisorRules(), [
            'customer_id' => [
                'required',
                'exists:customers,id',
                Rule::unique('customer_cards', 'customer_id')->ignore($customerCard->id),
            ],
            'expires_at' => ['required', 'date', 'after_or_equal:today'],
        ]));

        $supervisor = $this->authorizeSupervisor($validated['supervisor_pin']);
        $before = $this->cardSnapshot($customerCard);

        $customerCard->update([
            'customer_id' => (int) $validated['customer_id'],
            'expires_at' => $validated['expires_at'],
        ]);

        $this->auditCardAction('customer_card_details_updated', $customerCard->fresh('customer'), $supervisor, [
            'before' => $before,
            'after' => $this->cardSnapshot($customerCard->fresh('customer')),
            'reason' => $validated['supervisor_reason'] ?? null,
        ]);

        return back()->with('success', 'Dados do cartao atualizados com sucesso.');
    }

    public function toggleStatus(Request $request, CustomerCard $customerCard): RedirectResponse
    {
        $validated = $request->validate($this->supervisorRules());
        $supervisor = $this->authorizeSupervisor($validated['supervisor_pin']);
        $before = $this->cardSnapshot($customerCard);

        $customerCard->update([
            'status' => $customerCard->status === 'active' ? 'blocked' : 'active',
        ]);

        $this->auditCardAction('customer_card_status_changed', $customerCard->fresh('customer'), $supervisor, [
            'before' => $before,
            'after' => $this->cardSnapshot($customerCard->fresh('customer')),
            'reason' => $validated['supervisor_reason'] ?? null,
        ]);

        return back()->with('success', $customerCard->status === 'active'
            ? 'Cartao ativado com sucesso.'
            : 'Cartao bloqueado com sucesso.');
    }

    public function recharge(Request $request, CustomerCard $customerCard, CustomerCardService $service): RedirectResponse
    {
        $validated = $request->validate(array_merge($this->supervisorRules(), [
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:cash,card,transf'],
            'description' => ['nullable', 'string', 'max:180'],
        ]));

        $supervisor = $this->authorizeSupervisor($validated['supervisor_pin']);
        $before = $this->cardSnapshot($customerCard);

        try {
            $transaction = $service->recharge(
                $customerCard,
                round((float) $validated['amount'], 2),
                $validated['method'],
                session('operator_id'),
                $validated['description'] ?? null
            );

            $this->auditCardAction('customer_card_recharged', $customerCard->fresh('customer'), $supervisor, [
                'before' => $before,
                'after' => $this->cardSnapshot($customerCard->fresh('customer')),
                'transaction_id' => $transaction->id,
                'amount' => round((float) $validated['amount'], 2),
                'method' => $validated['method'],
                'description' => $validated['description'] ?? null,
                'reason' => $validated['supervisor_reason'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cartao recarregado com sucesso.');
    }

    public function redeem(Request $request, CustomerCard $customerCard, CustomerCardService $service): RedirectResponse
    {
        $validated = $request->validate(array_merge($this->supervisorRules(), [
            'points' => ['required', 'integer', 'min:100'],
        ]));

        $supervisor = $this->authorizeSupervisor($validated['supervisor_pin']);
        $before = $this->cardSnapshot($customerCard);

        try {
            $transaction = $service->redeem($customerCard, (int) $validated['points']);

            $this->auditCardAction('customer_card_points_redeemed', $customerCard->fresh('customer'), $supervisor, [
                'before' => $before,
                'after' => $this->cardSnapshot($customerCard->fresh('customer')),
                'transaction_id' => $transaction->id,
                'points' => (int) $validated['points'],
                'reason' => $validated['supervisor_reason'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pontos resgatados com sucesso.');
    }
    private function supervisorRules(): array
    {
        return [
            'supervisor_pin' => ['required', 'digits:8'],
            'supervisor_reason' => ['nullable', 'string', 'max:180'],
        ];
    }

    private function authorizeSupervisor(string $pin): Operator
    {
        $pinFingerprint = Operator::pinFingerprint($pin);
        $supervisor = Operator::where('active', true)
            ->where('pin_fingerprint', $pinFingerprint)
            ->first();

        if (!$supervisor) {
            $supervisor = Operator::where('active', true)
                ->whereNull('pin_fingerprint')
                ->get()
                ->first(fn (Operator $operator) => Hash::check($pin, $operator->pin));

            if ($supervisor && !Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($supervisor->id)->exists()) {
                $supervisor->forceFill(['pin_fingerprint' => $pinFingerprint])->save();
            }
        }

        if (!$supervisor || !OperatorPermissions::allowsAny($supervisor->role, ['security.manage', 'cash.audit', 'management.view'])) {
            AuditLogger::log('customer_card_supervisor_authorization_failed', 'CustomerCard', null, [
                'operator_id' => session('operator_id'),
                'operator_name' => session('operator_name'),
                'matched_operator_id' => $supervisor?->id,
                'matched_operator_name' => $supervisor?->name,
                'matched_operator_role' => $supervisor?->role,
                'reason' => $supervisor ? 'sem permissao de supervisor' : 'pin invalido',
            ]);

            throw ValidationException::withMessages([
                'supervisor_pin' => 'PIN de supervisor invalido ou sem permissao para autorizar esta acao.',
            ]);
        }

        return $supervisor;
    }

    private function auditCardAction(string $action, CustomerCard $card, Operator $supervisor, array $data = []): void
    {
        AuditLogger::log($action, 'CustomerCard', $card->id, array_merge([
            'card_number' => $card->card_number,
            'customer_id' => $card->customer_id,
            'customer_name' => $card->customer?->name,
            'operator_id' => session('operator_id'),
            'operator_name' => session('operator_name'),
            'supervisor_id' => $supervisor->id,
            'supervisor_name' => $supervisor->name,
            'supervisor_role' => $supervisor->role,
        ], $data));
    }

    private function cardSnapshot(CustomerCard $card): array
    {
        $card->loadMissing('customer');

        return [
            'customer_id' => $card->customer_id,
            'customer_name' => $card->customer?->name,
            'card_number' => $card->card_number,
            'points' => (int) $card->points,
            'balance' => (float) $card->balance,
            'level' => $card->level,
            'status' => $card->status,
            'status_label' => $card->status_label,
            'expires_at' => optional($card->expires_at)->toDateString(),
        ];
    }
    private function authorizationPayload(CustomerCardAuthorizationRequest $authorization): array
    {
        $authorization->loadMissing('card.customer', 'requester', 'reviewer');

        return [
            'id' => $authorization->id,
            'status' => $authorization->status,
            'amount' => (float) $authorization->amount,
            'reason' => $authorization->reason,
            'decision_note' => $authorization->decision_note,
            'card_number' => $authorization->card?->card_number,
            'customer_name' => $authorization->card?->customer?->name,
            'operator_name' => $authorization->requester?->name,
            'supervisor_name' => $authorization->reviewer?->name,
            'expires_at' => optional($authorization->expires_at)->toDateTimeString(),
            'created_at' => optional($authorization->created_at)->toDateTimeString(),
        ];
    }
}
