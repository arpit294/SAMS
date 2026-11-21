<x-layout>
    <x-sidebar />

    <div class="wrapper d-flex flex-column min-vh-100 bg-body-tertiary">
        <x-navbar />

        <div class="container-fluid px-4 py-3">
            <x-table-crud-card heading="Student Report" subheading="Each student with Paid or Pending status from January to December"
                :filters="[
                    [
                        'id' => 'sportFilter',
                        'placeholder' => 'All Sports',
                        'options' => $sports->toArray(),
                    ],
                    [
                        'id' => 'batchFilter',
                        'placeholder' => 'All Batches',
                        'options' => $batches->toArray(),
                    ],
                    [
                        'id' => 'levelFilter',
                        'placeholder' => 'All Levels',
                        'options' => $levels->toArray(),
                    ],
                    [
                        'id' => 'yearFilter',
                        'placeholder' => 'All Years',
                        'default' => (string) now()->year,
                        'options' => $years,
                    ],
                ]">

                {{ $dataTable->table(['class' => 'table table-sm table-hover align-middle mb-0']) }}

            </x-table-crud-card>
        </div>
    </div>

    @push('scripts')
        {{ $dataTable->scripts() }}
    @endpush
</x-layout>
