<?php

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $searchQuery = '';

    public bool $showForm = false;
    public ?Member $editing = null;

    public string $member_number = '';
    public string $name = '';
    public string $birth_date = '';

    public function filter()
    {
        $this->searchQuery = $this->search;
        $this->resetPage();
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function showEditForm(Member $member)
    {
        $this->editing = $member;
        $this->member_number = $member->member_number;
        $this->name = $member->name;
        $this->birth_date = $member->birth_date->format('Y-m-d');
        $this->showForm = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'member_number' => 'required|string|unique:members,member_number,' . optional($this->editing)->id,
            'name' => 'required|string',
            'birth_date' => 'required|date',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
        } else {
            Member::create($validated);
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(Member $member)
    {
        $member->delete();
        session()->flash('message', 'Member deleted.');
    }

    public function resetForm()
    {
        $this->reset(['editing', 'member_number', 'name', 'birth_date']);
    }

    public function render(): mixed
    {
        $members = Member::query()
            ->when($this->searchQuery, fn($q) => $q->where(fn($q) => $q->where('name', 'like', '%' . $this->searchQuery . '%')
                ->orWhere('member_number', 'like', '%' . $this->searchQuery . '%')))
            ->latest()->paginate(8);

        return view('livewire.member.index', compact('members'));
    }
};

?>
<div class="flex flex-col gap-6 p-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Members</h1>
        <button wire:click="showCreateForm" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            + Add Member
        </button>
    </div>
    <hr>
    <div class="flex gap-2 items-center">
        <input
            type="text"
            wire:model.defer="search"
            placeholder="Search by name or number..."
            class="border rounded px-4 py-2 w-full max-w-md"
        />
        <button
            wire:click="filter"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
        >
            Filter
        </button>
    </div>

    @if (session()->has('message'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            class="text-green-600 transition-opacity duration-500"
            style="display: none;"
        >
            {{ session('message') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 divide-y divide-gray-200 mt-4">
            <thead>
            <tr>
                <th class="px-4 py-2 text-left">No</th>
                <th class="px-4 py-2 text-left">Member Number</th>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Date of Birth</th>
                <th class="px-4 py-2 text-left">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($members as $index => $member)
                <tr class="hover:bg-amber-950">
                    <td class="px-4 py-2">{{ $members->firstItem() + $index }}</td>
                    <td class="px-4 py-2">{{ $member->member_number }}</td>
                    <td class="px-4 py-2">{{ $member->name }}</td>
                    <td class="px-4 py-2">{{ $member->birth_date->format('d M Y') }}</td>
                    <td class="px-4 py-2 space-x-2">
                        <button wire:click="showEditForm({{ $member }})" class="text-blue-600 hover:underline">
                            Edit
                        </button>
                        <button wire:click="delete({{ $member }})" class="text-red-600 hover:underline"
                                onclick="return confirm('Are you sure?')">Delete
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No members found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $members->links() }}
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-amber-950 rounded-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">
                    {{ $editing ? 'Edit Member' : 'Add Member' }}
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1">Member Number</label>
                        <input type="text" wire:model="member_number" class="w-full border px-3 py-2 rounded">
                        @error('member_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full border px-3 py-2 rounded">
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block mb-1">Birth Date</label>
                        <input type="date" wire:model="birth_date" class="w-full border px-3 py-2 rounded">
                        @error('birth_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button wire:click="$set('showForm', false)"
                            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel
                    </button>
                    <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        {{ $editing ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
