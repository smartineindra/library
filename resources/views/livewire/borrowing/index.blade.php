<?php

use App\Models\Borrowing;
use App\Models\Book;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $searchQuery = '';

    public bool $showForm = false;
    public ?Borrowing $editing = null;

    public ?string $member_id = null;
    public ?string $book_id = null;
    public ?string $borrowed_at = null;

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

    public function showEditForm(Borrowing $borrowing)
    {
        $this->editing = $borrowing;
        $this->member_id = $this->editing->member_id;
        $this->book_id = $this->editing->book_id;
        $this->borrowed_at = $this->editing->borrowed_at->format('Y-m-d');
        $this->showForm = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'member_id' => 'required|exists:members,id',
            'book_id' => 'required|exists:books,id',
            'borrowed_at' => 'required|date',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            session()->flash('message', 'Borrowing updated successfully.');
        } else {
            Borrowing::create($validated);
            session()->flash('message', 'Borrowing created successfully.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(Borrowing $borrowing)
    {
        $borrowing->delete();
        session()->flash('message', 'Borrowing deleted.');
    }

    public function resetForm()
    {
        $this->reset(['editing', 'member_id', 'book_id', 'borrowed_at']);
    }

    public function render(): mixed
    {
        $borrowings = Borrowing::with(['member', 'book'])
            ->when($this->searchQuery, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%');
                })->orWhereHas('book', function ($q) {
                    $q->where('title', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->latest()
            ->paginate(8);

        $members = Member::orderBy('name')->get();
        $books = Book::orderBy('title')->get();

        return view('livewire.borrowing.index', compact('borrowings', 'members', 'books'));
    }
};

?>
<div class="flex flex-col gap-6 p-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Borrowings</h1>
        <button wire:click="showCreateForm" class="bg-green-600 text-white px-4 py-2 rounded">+ Add Borrowing</button>
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
            <th class="px-4 py-2">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($borrowings as $index => $borrowing)
            <tr class="hover:bg-amber-950">
                <td class="px-4 py-2">{{ $borrowings->firstItem() + $index }}</td>
                <td class="px-4 py-2">{{ $borrowing->member->name }}</td>
                <td class="px-4 py-2">{{ $borrowing->book->title }}</td>
                <td class="px-4 py-2">{{ $borrowing->borrowed_at->format('d M Y') }}</td>
                <td class="px-4 py-2 space-x-2">
                    <button wire:click="showEditForm('{{ $borrowing }}')" class="text-blue-600 hover:underline">Edit</button>
                    <button wire:click="delete('{{ $borrowing }}')" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No borrowings found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $borrowings->links() }}
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-amber-950 p-6 rounded-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">{{ $editing ? 'Edit Borrowing' : 'Add Borrowing' }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1">Member</label>
                        <select wire:model="member_id" class="w-full border rounded px-3 py-2 bg-amber-950">
                            <option value="">-- Select Member --</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                        @error('member_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Book</label>
                        <select wire:model="book_id" class="w-full border rounded px-3 py-2 bg-amber-950">
                            <option value="">-- Select Book --</option>
                            @foreach ($books as $book)
                                <option value="{{ $book->id }}">{{ $book->title }}</option>
                            @endforeach
                        </select>
                        @error('book_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Borrowed At</label>
                        <input type="date" wire:model="borrowed_at" class="w-full border rounded px-3 py-2" />
                        @error('borrowed_at') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button wire:click="$set('showForm', false)" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        {{ $editing ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
