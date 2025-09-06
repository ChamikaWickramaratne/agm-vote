@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Positions
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                {{-- Success message --}}
                @if (session('success'))
                    <div class="mb-4 text-green-600">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Add new position --}}
                <a href="{{ route('positions.create') }}"
                   class="inline-block mb-4 px-4 py-2 bg-green-600 text-white rounded-md">
                    + Add New Position
                </a>

                {{-- Search form --}}
                <form method="GET" action="{{ route('positions.index') }}" class="mb-4">
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="Search positions..."
                           class="border rounded px-3 py-2 w-1/3">
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md">
                        Search
                    </button>
                </form>

                {{-- Table of positions --}}
                <table class="min-w-full border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 border">ID</th>
                            <th class="px-4 py-2 border">Name</th>
                            <th class="px-4 py-2 border">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $position)
                            <tr>
                                <td class="px-4 py-2 border">{{ $position->id }}</td>
                                <td class="px-4 py-2 border">{{ $position->name }}</td>
                                <td class="px-4 py-2 border">{{ $position->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 border text-center">No positions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endsection
