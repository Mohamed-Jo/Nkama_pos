@extends('layouts.admin')

@section('content')
    <div class="flex justify-between mb-6">

        <h1 class="text-2xl font-bold">
            👥 Clientes
        </h1>

        <a href="{{ route('customers.create') }}" class="accent-bg text-black px-4 py-2 rounded-lg font-semibold">

            + Novo Cliente

        </a>

    </div>

    <div class="card rounded-xl overflow-hidden">

        <table class="w-full">

            <thead class="bg-gray-800">
                <tr>
                    <th class="p-4 text-left">Nome</th>
                    <th class="p-4 text-left">Telefone</th>
                    <th class="p-4 text-left">Email</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($customers as $customer)
                    <tr class="border-t border-gray-800">

                        <td class="p-4">
                            {{ $customer->name }}
                        </td>

                        <td class="p-4">
                            {{ $customer->phone }}
                        </td>

                        <td class="p-4">
                            {{ $customer->email }}
                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>
@endsection
