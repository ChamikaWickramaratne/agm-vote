@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Create Position
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                {{-- Show validation errors --}}
                @if ($errors->any())
                    <div class="mb-4 text-red-600">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('positions.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="title" class="block text-gray-700">Position Title</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="border rounded w-full px-3 py-2">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md">
                        Save
                    </button>
                </form>

            </div>
        </div>
    </div>
@endsection
