<?php

use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    public array $chartData = [];

    public function mount()
    {
        $this->loadChartData();
    }


    public function loadChartData()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = Borrowing::select(
            'book_id',
            DB::raw('COUNT(*) as total'),
            'books.title'
        )
            ->join('books', 'books.id', '=', 'borrowings.book_id')
            ->whereBetween('borrowed_at', [$startOfWeek, $endOfWeek])
            ->groupBy('book_id', 'books.title')
            ->orderBy('total', 'desc');

        //        dd([
//            'sql' => $query->toSql(),
//            'bindings' => $query->getBindings(),
//        ]);

        $this->chartData = $query->get()
            ->map(fn($item) => [
                'label' => $item->title,  // Nama buku sebagai label
                'data' => $item->total,   // Jumlah peminjaman sebagai data
            ])
            ->toArray();

        $this->dispatch('refreshChart', [
            'labels' => collect($this->chartData)->pluck('label')->toArray(),
            'data' => collect($this->chartData)->pluck('data')->toArray(),
        ]);
    }

    public function render(): mixed
    {
        return view('livewire.borrowing.weekly-chart');
    }
};
?>

<div class="flex flex-col gap-6 p-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">This week's Book Borrowings Recap</h1>
    </div>

    <div class="bg-white rounded shadow p-4">
        <canvas id="borrowingsChart" height="100"></canvas>
    </div>
</div>

<script>
    let chartInstance;

    function renderChart(labels, data) {
        const ctx = document.getElementById('borrowingsChart').getContext('2d');

        if (chartInstance) {
            chartInstance.destroy();
        }

        console.log(labels, "LAbel bro");
        console.log(data, "DAta bro");

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Book Borrowing',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }


    window.addEventListener('refreshChart', event => {
        renderChart(event.detail[0].labels, event.detail[0].data);
    });
</script>
