@extends('layouts.admin')

@section('content')

<div class="flex justify-between items-center mb-6">

    <h1 class="text-2xl font-bold">
        📁 Categorias
    </h1>

    <a href="{{ route('categories.create') }}"
       class="accent-bg text-black px-4 py-2 rounded-lg font-semibold">

        + Nova Categoria

    </a>

</div>

<div class="card rounded-xl overflow-hidden">

    <table class="w-full">

        <thead class="bg-gray-800">
            <tr>
                <th class="p-4 text-left">Nome</th>
                <th class="p-4 text-left">Descrição</th>
            </tr>
        </thead>

        <tbody>

            @foreach($categories as $category)

                <tr class="border-t border-gray-800">

                    <td class="p-4">
                        {{ $category->name }}
                    </td>

                    <td class="p-4">
                        {{ $category->description }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</div>

@endsection