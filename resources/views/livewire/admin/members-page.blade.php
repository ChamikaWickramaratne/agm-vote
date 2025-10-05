{{-- Rendered inside layouts.app via #[Layout('layouts.app')] --}}

<x-slot name="header">
    <h2 class="font-semibold text-2xl text-[#4F200D] leading-tight">
        Members
    </h2>
</x-slot>

<div class="py-10 max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

    {{-- Flash --}}
    @if (session('ok'))
        <div class="p-4 rounded-lg bg-green-100 text-green-800 shadow">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Bulk Import --}}
    <div class="bg-[#F6F1E9] shadow-md rounded-2xl p-8 border border-[#FFD93D]">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-[#4F200D]">📥 Bulk Member Import (CSV)</h3>
            <a href="{{ route('admin.members.template') }}" class="text-blue-600 underline">
                Download CSV template
            </a>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Upload CSV</label>
                <input type="file" wire:model="importFile" accept=".csv,text/csv"
                    class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                @error('importFile') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center space-x-2">
                    <input type="checkbox" wire:model="dryRun">
                    <span>validate csv</span>
                </label>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button wire:click="import" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Import
            </button>
            <div wire:loading>Processing…</div>
        </div>

        @if ($progress > 0)
            <div class="mt-4">
                <div class="h-3 rounded bg-gray-200 overflow-hidden">
                    <div class="h-3 bg-green-500" style="width: {{ $progress }}%"></div>
                </div>
                <div class="text-sm text-gray-600 mt-1">{{ $progress }}%</div>
            </div>
        @endif

        <div class="mt-6">
            <h4 class="font-semibold text-[#4F200D] mb-2">Report</h4>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div><dt class="text-gray-500 text-sm">Total</dt><dd class="font-medium">{{ $report['total'] }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Valid</dt><dd class="font-medium">{{ $report['valid'] }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Created</dt><dd class="font-medium">{{ $report['created'] }}</dd></div>
                <div><dt class="text-gray-500 text-sm">Skipped</dt><dd class="font-medium">{{ $report['skipped'] }}</dd></div>
            </dl>

            @if (!empty($report['errors']))
                <div class="mt-4">
                    <h5 class="font-medium mb-2">Errors</h5>
                    <div class="max-h-64 overflow-auto border rounded">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Row</th>
                                    <th class="px-3 py-2 text-left">Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['errors'] as $e)
                                    <tr class="border-t">
                                        <td class="px-3 py-2">{{ $e['row'] }}</td>
                                        <td class="px-3 py-2">{{ $e['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button wire:click="downloadErrors"
                            class="mt-3 px-3 py-2 border rounded hover:bg-gray-50">
                        Download error CSV
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Create --}}
    <div class="bg-[#F6F1E9] shadow-md rounded-2xl p-8 border border-[#FFD93D]">
        <h3 class="font-bold text-lg text-[#4F200D] mb-6">➕ Add Member</h3>

        <form wire:submit="save" class="grid gap-6 sm:grid-cols-2" enctype="multipart/form-data">
            
            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Title</label>
                <select name="title" wire:model.defer="title" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                    <option value="">-- Select --</option>
                    <option>Mr.</option>
                    <option>Mrs.</option>
                    <option>Miss</option>
                    <option>Ms</option>
                </select>
                @error('title') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- First Name --}}
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">First Name</label>
                <input name="first_name" type="text" wire:model.defer="first_name" 
                       class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                @error('first_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Last Name --}}
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Last Name</label>
                <input name="last_name" type="text" wire:model.defer="last_name" 
                       class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                @error('last_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-[#4F200D]">Email (optional)</label>
                <input name="email" type="email" wire:model.defer="email" 
                       class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
                @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Bio --}}
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-[#4F200D]">Bio</label>
                <textarea name="bio" wire:model.defer="bio" rows="3" 
                          class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]"></textarea>
                @error('bio') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
            <!-- Add under Bio, for example -->
            <div>
            <label class="block text-sm font-medium text-[#4F200D]">Photo</label>
            <input name="photoUpload" type="file" wire:model="photoUpload"
                    accept="image/*"
                    class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2 focus:ring-2 focus:ring-[#FF9A00]">
            @error('photoUpload') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Submit --}}
            <div class="sm:col-span-2">
                <button type="submit"
                    class="inline-flex items-center px-6 py-2 bg-[#FF9A00] text-white rounded-lg shadow hover:bg-[#FFD93D] hover:text-[#4F200D] transition duration-200">
                    ✅ Create
                </button>
            </div>
        </form>
    </div>

    {{-- List --}}
    <div class="bg-[#F6F1E9] shadow-md rounded-2xl p-8 border border-[#FFD93D]">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg text-[#4F200D]">👥 All Members</h3>
            <input type="text" placeholder="🔍 Search…"
                   wire:model.debounce.400ms="search"
                   class="border border-[#FFD93D] rounded-lg p-2 w-56 focus:ring-2 focus:ring-[#FF9A00]">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border border-[#FFD93D] rounded-lg overflow-hidden">
                <thead class="bg-[#FFD93D] text-[#4F200D]">
                    <tr>
                        <th class="py-2 px-4">Title</th>
                        <th class="py-2 px-4">First Name</th>
                        <th class="py-2 px-4">Last Name</th>
                        <th class="py-2 px-4">Email</th>
                        <th class="py-2 px-4">Bio</th>
                        <th class="py-2 px-4">Photo</th>
                        <th class="py-2 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FFD93D]">
                @forelse($members as $m)
                    <tr class="hover:bg-[#FFF6D6] transition">
                        <td class="py-2 px-4">{{ $m->title }}</td>
                        <td class="py-2 px-4">{{ $m->first_name }}</td>
                        <td class="py-2 px-4">{{ $m->last_name }}</td>
                        <td class="py-2 px-4">{{ $m->email }}</td>
                        <td class="py-2 px-4">{{ $m->bio }}</td>
                        <td class="py-2 px-4">
                            @if($m->photo)
                                <img src="{{ Storage::url($m->photo) }}" class="h-12 rounded-lg shadow">
                            @endif
                        </td>
                        <td class="py-2 px-4 space-x-2">
                        <button dusk="edit-member"
                                wire:click="startEdit({{ $m->id }})"
                                class="px-3 py-1 rounded-lg bg-[#4F200D] text-white hover:bg-[#FF9A00] transition">
                            ✏️ Edit
                        </button>

                        <button dusk="delete-member"
                                wire:click="delete({{ $m->id }})"
                                onclick="return confirm('Delete this member?')"
                                class="px-3 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                            🗑️ Delete
                        </button>


                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-6 text-center text-gray-500">No members found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $members->links() }}
        </div>
    </div>

    {{-- Edit Modal --}}
    <@if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="open=false; $wire.call('cancelEdit')"></div>

        {{-- Panel --}}
        <div class="relative bg-white w-full max-w-2xl rounded-2xl shadow-lg p-6 mx-4"
            @keydown.escape.window="open=false; $wire.call('cancelEdit')">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg text-[#4F200D]">
                    ✏️ Edit Member @if($editingId)#{{ $editingId }}@endif
                </h3>
                <button class="p-2 rounded hover:bg-gray-100"
                        @click="open=false; $wire.call('cancelEdit')">✖</button>
            </div>

            <form wire:submit.prevent="update" class="grid gap-6 sm:grid-cols-2" enctype="multipart/form-data">
                <div>
                    <label class="block text-sm font-medium text-[#4F200D]">Title</label>
                    <select wire:model.defer="editTitle" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2">
                        <option value="">-- Select --</option>
                        <option>Mr.</option>
                        <option>Mrs.</option>
                        <option>Miss</option>
                        <option>Ms</option>
                    </select>
                    @error('editTitle') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#4F200D]">First Name</label>
                    <input dusk="edit-first-name" type="text" wire:model.defer="editFirstName" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2">
                    @error('editFirstName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#4F200D]">Last Name</label>
                    <input dusk="edit-last-name" type="text" wire:model.defer="editLastName" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2">
                    @error('editLastName') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#4F200D]">Email (Optional)</label>
                    <input dusk="edit-email" type="email" wire:model.defer="editEmail" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2">
                    @error('editEmail') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-[#4F200D]">Bio</label>
                    <textarea dusk="edit-bio" rows="3" wire:model.defer="editBio" class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2"></textarea>
                    @error('editBio') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#4F200D]">Photo (Optional)</label>
                    <input type="file" wire:model="editPhotoUpload" accept="image/*"
                        class="mt-1 w-full border border-[#FFD93D] rounded-lg p-2">
                    @error('editPhotoUpload') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror>

                    {{-- Preview (new upload or current) --}}
                    <div class="mt-2">
                        @if($editPhotoUpload)
                            <img src="{{ $editPhotoUpload->temporaryUrl() }}" class="h-16 rounded-lg shadow">
                        @elseif($editingId && optional(\App\Models\Member::find($editingId))->photo)
                            <img src="{{ Storage::url(\App\Models\Member::find($editingId)->photo) }}" class="h-16 rounded-lg shadow">
                        @endif
                    </div>
                </div>

                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" @click="open=false; $wire.call('cancelEdit')"
                            class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Cancel</button>
                    <button type="submit"
                            class="px-6 py-2 rounded-lg bg-[#4F200D] text-white hover:bg-[#FF9A00]">Save</button>
                </div>
            </form>
        </div>
    @endif
</div>
