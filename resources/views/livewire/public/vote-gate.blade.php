<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Voting — Session #{{ $session->id }}
    </h2>
</x-slot>

<div class="py-8 max-w-md mx-auto sm:px-6 lg:px-8">
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <p class="text-gray-700">
            Enter your 6-character code to access the ballot for this voting session.
        </p>

        <form wire:submit.prevent="verify" class="space-y-4">
            <div>
                <label class="block text-sm font-medium">Your Code</label>
                <input type="text" wire:model.defer="code" maxlength="6"
                       class="mt-1 w-full border rounded p-2 uppercase"
                       placeholder="e.g. A7K9Z2">
                @error('code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    Continue
                </button>
                <a href="{{ url()->previous() }}" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    Back
                </a>
            </div>
        </form>
    </div>
</div>
