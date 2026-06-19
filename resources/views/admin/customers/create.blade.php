@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold mb-6">
        ➕ Novo Cliente
    </h1>

    <form method="POST" action="{{ route('customers.store') }}" class="card p-6 rounded-xl space-y-4">

        @csrf

        <input name="name" placeholder="Nome do cliente" class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg">

        <input name="phone" placeholder="Telefone" class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg">

        <input name="email" placeholder="Email" class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg">

        <textarea name="address" placeholder="Morada" class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg"></textarea>

        <button class="accent-bg text-black px-6 py-3 rounded-lg font-semibold">

            Guardar Cliente

        </button>

    </form>
@endsection
