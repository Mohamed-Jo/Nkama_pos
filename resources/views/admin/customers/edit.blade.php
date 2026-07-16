@extends('layouts.admin')

@section('page-title', 'Editar Cliente')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px;">
        <h1 style="font-size:24px; font-weight:800; margin:0;">Editar Cliente</h1>
        <a href="{{ route('admin.customers.index') }}" class="btn-primary">Voltar</a>
    </div>

    @if($errors->any())
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="card p-6 rounded-xl space-y-4" style="max-width:720px;">
        @csrf
        @method('PUT')

        <label style="display:block; color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">Nome
            <input name="name" value="{{ old('name', $customer->name) }}" required placeholder="Nome do cliente">
        </label>

        <label style="display:block; color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">Telefone
            <input name="phone" value="{{ old('phone', $customer->phone) }}" placeholder="Telefone">
        </label>

        <label style="display:block; color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">Email
            <input name="email" type="email" value="{{ old('email', $customer->email) }}" placeholder="Email">
        </label>

        <label style="display:block; color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase;">Morada
            <textarea name="address" placeholder="Morada" style="width:100%; min-height:110px; margin-top:6px; padding:12px; border-radius:8px; border:1px solid var(--border); background:var(--input-bg); color:var(--input-text);">{{ old('address', $customer->address) }}</textarea>
        </label>

        <label style="display:flex; gap:8px; align-items:center; color:var(--text);">
            <input type="checkbox" name="status" value="1" @checked(old('status', $customer->status))>
            Cliente ativo
        </label>

        @if($customer->card)
            <div style="background:rgba(56,189,248,.08); border:1px solid rgba(56,189,248,.25); color:#bae6fd; padding:12px; border-radius:8px;">
                Cartao associado:
                <a href="{{ route('admin.customer-cards.show', $customer->card) }}" style="color:#38bdf8; font-weight:800;">{{ $customer->card->card_number }}</a>
            </div>
        @endif

        <button class="btn-primary" type="submit">Guardar alterações</button>
    </form>
@endsection
