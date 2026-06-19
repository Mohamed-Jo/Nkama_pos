@extends('layouts.admin')

@section('content')

<div class="p-6">

    <h1 class="text-3xl font-bold mb-6">
        Restaurante
    </h1>

    <div class="grid grid-cols-4 gap-4">

        @foreach($tables as $table)

            <a href="/admin/restaurant/table/{{ $table->id }}">

                <div class="
                    p-8
                    rounded-xl
                    text-center
                    text-white

                    @if($table->status == 'free')
                        bg-green-600
                    @elseif($table->status == 'occupied')
                        bg-red-600
                    @else
                        bg-orange-500
                    @endif
                ">

                    <div class="text-2xl font-bold">
                        {{ $table->name }}
                    </div>

                    <div class="mt-2">
                        {{ strtoupper($table->status) }}
                    </div>

                </div>

            </a>

        @endforeach

    </div>

</div>

@endsection