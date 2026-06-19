@extends('layouts.admin')

@section('content')

<h1 class="text-2xl font-bold mb-6">
    ➕ Nova Categoria
</h1>

<form method="POST"
      action="{{ route('categories.store') }}"
      class="card p-6 rounded-xl space-y-4">

    @csrf

    <input
        name="name"
        placeholder="Nome da categoria"
        class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg">

    <textarea
        name="description"
        placeholder="Descrição"
        class="w-full bg-gray-800 border border-gray-700 p-3 rounded-lg"></textarea>

    <button
        class="accent-bg text-black px-6 py-3 rounded-lg font-semibold">

        Guardar Categoria

    </button>

</form>

@endsection