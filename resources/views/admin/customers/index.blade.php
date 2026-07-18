@extends('layouts.admin')

@section('page-title', 'Clientes')

@section('content')
    <div class="flex justify-between mb-6">
        <h1 class="text-2xl font-bold">Clientes</h1>
        <a href="{{ route('admin.customers.create') }}" class="accent-bg text-black px-4 py-2 rounded-lg font-semibold">+ Novo Cliente</a>
    </div>

    @if(session('success'))
        <div style="background:rgba(16,185,129,.15); border:1px solid #10b981; color:#86efac; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    <div class="card rounded-xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-800">
                <tr>
                    <th class="p-4 text-left">Nome</th>
                    <th class="p-4 text-left">Telefone</th>
                    <th class="p-4 text-left">Email</th>
                    <th class="p-4 text-left">Estado</th>
                    <th class="p-4 text-left">Cartao</th>
                    <th class="p-4 text-left">Pontos</th>
                    <th class="p-4 text-left">Nivel</th>
                    <th class="p-4 text-left">Acao</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                    <tr class="border-t border-gray-800">
                        <td class="p-4">{{ $customer->name }}</td>
                        <td class="p-4">{{ $customer->phone ?: '-' }}</td>
                        <td class="p-4">{{ $customer->email ?: '-' }}</td>
                        <td class="p-4">{{ $customer->status ? 'Ativo' : 'Inativo' }}</td>
                        <td class="p-4">
                            @if($customer->card)
                                <a href="{{ route('admin.customer-cards.show', $customer->card) }}" style="color:#38bdf8; font-weight:700;">{{ $customer->card->card_number }}</a>
                            @else
                                <span style="color:#94a3b8;">Sem cartao</span>
                            @endif
                        </td>
                        <td class="p-4">{{ number_format($customer->card->points ?? 0, 0, ',', '.') }}</td>
                        <td class="p-4">{{ $customer->card->level ?? '-' }}</td>
                        <td class="p-4">
                            <a href="{{ route('admin.customers.edit', $customer) }}" style="color:#38bdf8; font-weight:800;">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-pagination">
        {{ $customers->links() }}
    </div>
@endsection
