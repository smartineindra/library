<?php

use App\Models\ReturnBook;
use App\Models\Borrowing;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $searchQuery = '';

    public bool $showForm = false;
    public ?ReturnBook $editing = null;

    public ?string $borrowing_id = null;
    public ?string $returned_at = null;

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

    public function showEditForm(ReturnBook $returnBook)
    {
        $this->editing = $returnBook;
        $this->borrowing_id = $this->editing->borrowing_id;
        $this->returned_at = $this->editing->returned_at->format('Y-m-d');
        $this->showForm = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'borrowing_id' => 'required|exists:borrowings,id',
            'returned_at' => 'required|date',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            session()->flash('message', 'Return record updated successfully.');
        } else {
            ReturnBook::create($validated);
            session()->flash('message', 'Return record created successfully.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(ReturnBook $returnBook)
    {
        $returnBook->delete();
        session()->flash('message', 'Return record deleted.');
    }

    public function resetForm()
    {
        $this->reset(['editing', 'borrowing_id', 'returned_at']);
    }

    public function render(): mixed
    {
        $returnBooks = ReturnBook::with(['borrowing.member', 'borrowing.book'])
            ->when($this->searchQuery, function ($query) {
                $query->whereHas('borrowing.member', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%');
                })->orWhereHas('borrowing.book', function ($q) {
                    $q->where('title', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->latest()
            ->paginate(8);

        $borrowings = Borrowing::with(['member', 'book'])->get();

        return view('livewire.return-book.index', compact('returnBooks', 'borrowings'));
    }
};

?>
<div class="flex flex-col gap-6 p-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Return Books</h1>
        <button wire:click="showCreateForm" class="bg-green-600 text-white px-4 py-2 rounded">+ Add Return</button>
    </div>
    <hr>
    <div class="flex gap-2 items-center">
        <input
            type="text"
            wire:model.defer="search"
            placeholder="Search by member or book..."
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

    <table class="min-w-full border border-gray-300 divide-y divide-gray-200 mt-4">
        <thead>
        <tr>
            <th class="px-4 py-2">No</th>
            <th class="px-4 py-2">Member</th>
            <th class="px-4 py-2">Book</th>
            <th class="px-4 py-2">Borrowed At</th>
            <th class="px-4 py-2">Returned At</th>
            <th class="px-4 py-2">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($returnBooks as $index => $returnBook)
            <tr class="hover:bg-amber-950">
                <td class="px-4 py-2">{{ $returnBooks->firstItem() + $index }}</td>
                <td class="px-4 py-2">{{ $returnBook->borrowing->member->name }}</td>
                <td class="px-4 py-2">{{ $returnBook->borrowing->book->title }}</td>
                <td class="px-4 py-2">{{ \Carbon\Carbon::parse($returnBook->borrowing->borrowed_at)->format('d M Y') }}</td>
                <td class="px-4 py-2">{{ \Carbon\Carbon::parse($returnBook->returned_at)->format('d M Y') }}</td>
                <td class="px-4 py-2 space-x-2">
                    <button wire:click="showEditForm('{{ $returnBook }}')" class="text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete('{{ $returnBook }}')" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No books found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $returnBooks->links() }}
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-amber-950 p-6 rounded-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">{{ $editing ? 'Edit Return' : 'Add Return' }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1">Borrowing</label>
                        <select wire:model="borrowing_id" class="w-full border rounded px-3 py-2 bg-amber-950">
                            <option value="">-- Select Borrowing --</option>
                            @foreach ($borrowings as $borrowing)
                                <option value="{{ $borrowing->id }}">
                                    {{ $borrowing->member->name }} - {{ $borrowing->book->title }} ({{ \Carbon\Carbon::parse($borrowing->borrowed_at)->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('borrowing_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Returned At</label>
                        <input type="date" wire:model="returned_at" class="w-full border rounded px-3 py-2" />
                        @error('returned_at') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
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
