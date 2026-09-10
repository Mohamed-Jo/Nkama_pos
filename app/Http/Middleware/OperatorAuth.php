<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Operator;
use App\Services\CurrentCompany;
use Closure;
use Illuminate\Http\Request;

class OperatorAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('operator_id')) {
            return redirect('/kiosk');
        }

        $operator = Operator::find(session('operator_id'));

        if (!$operator || !$operator->active) {
            session()->forget(['operator_id', 'operator_name', 'operator_role', 'company_id', 'company_name']);

            return redirect('/kiosk');
        }

        $companyId = $operator->role === 'super_user'
            ? (session('company_id') ?: $operator->company_id ?: CurrentCompany::defaultId())
            : ($operator->company_id ?: CurrentCompany::defaultId());
        $companyName = $companyId && CurrentCompany::hasCompaniesTable()
            ? Company::whereKey($companyId)->value('name')
            : null;

        session([
            'operator_name' => $operator->name,
            'operator_role' => $operator->role,
            'company_id' => $companyId,
            'company_name' => $companyName,
        ]);

        return $next($request);
    }
}
