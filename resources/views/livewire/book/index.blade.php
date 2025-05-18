<?php

use App\Models\Book;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $searchQuery = '';

    public bool $showForm = false;
    public ?Book $editing = null;

    public ?string $title = null;
    public ?string $publisher = null;
    public ?string $dimensions = null;
    public int $stock = 0;

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

    public function showEditForm(Book $book)
    {
        $this->editing = $book;
        $this->title = $this->editing->title;
        $this->publisher = $this->editing->publisher;
        $this->dimensions = $this->editing->dimensions;
        $this->stock = $this->editing->stock;
        $this->showForm = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'publisher' => 'required|string|max:255',
            'dimensions' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:0',
        ]);

        if ($this->editing) {
            $this->editing->update($validated);
            session()->flash('message', 'Book updated successfully.');
        } else {
            Book::create($validated);
            session()->flash('message', 'Book created successfully.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(Book $book)
    {
        $book->delete();
        session()->flash('message', 'Book deleted.');
    }

    public function resetForm()
    {
        $this->reset(['editing', 'title', 'publisher', 'dimensions', 'stock']);
    }

    public function render(): mixed
    {
        $books = Book::query()
            ->when($this->searchQuery, fn($q) => $q->where(fn($q) => $q->where('title', 'like', '%' . $this->searchQuery . '%')
                ->orWhere('publisher', 'like', '%' . $this->searchQuery . '%')))
            ->latest()->paginate(8);

        return view('livewire.book.index', compact('books'));
    }
};

?>
<div class="flex flex-col gap-6 p-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Books</h1>
        <button wire:click="showCreateForm" class="bg-green-600 text-white px-4 py-2 rounded">+ Add Book</button>
    </div>
    <hr>
    <div class="flex gap-2 items-center">
        <input
            type="text"
            wire:model.defer="search"
            placeholder="Search by title or publisher..."
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
            <th class="px-4 py-2">Title</th>
            <th class="px-4 py-2">Publisher</th>
            <th class="px-4 py-2">Dimensions</th>
            <th class="px-4 py-2">Stock</th>
            <th class="px-4 py-2">Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse($books as $index => $book)
            <tr class="hover:bg-amber-950">
                <td class="px-4 py-2">{{ $books->firstItem() + $index }}</td>
                <td class="px-4 py-2">{{ $book->title }}</td>
                <td class="px-4 py-2">{{ $book->publisher }}</td>
                <td class="px-4 py-2">{{ $book->dimensions ?? '-' }}</td>
                <td class="px-4 py-2">{{ $book->stock }}</td>
                <td class="px-4 py-2 space-x-2">
                    <button wire:click="showEditForm('{{ $book }}')" class="text-blue-600 hover:underline">Edit
                    </button>
                    <button wire:click="delete('{{ $book }}')" class="text-red-600 hover:underline"
                            onclick="return confirm('Are you sure?')">Delete
                    </button>
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
        {{ $books->links() }}
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-amber-950 p-6 rounded-lg w-full max-w-md">
                <h2 class="text-xl font-bold mb-4">{{ $editing ? 'Edit Book' : 'Add Book' }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1">Title</label>
                        <input type="text" wire:model="title" class="w-full border rounded px-3 py-2"/>
                        @error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Publisher</label>
                        <input type="text" wire:model="publisher" class="w-full border rounded px-3 py-2"/>
                        @error('publisher') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Dimensions</label>
                        <input type="text" wire:model="dimensions" class="w-full border rounded px-3 py-2"/>
                        @error('dimensions') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-1">Stock</label>
                        <input type="number" wire:model="stock" class="w-full border rounded px-3 py-2" min="0"/>
                        @error('stock') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
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
