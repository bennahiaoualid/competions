@props(['paginator', 'perPageOptions' => [5, 10, 25, 50, 100], 'defaultPerPage' => 5])

<div class="flex justify-end items-center gap-4 mt-4 flex-wrap">
    {{-- Laravel Pagination --}}
    {{ $paginator->appends(['perPage' => request('perPage', $defaultPerPage)])->links() }}

    {{-- Per Page Dropdown --}}
    <form method="GET">
        <x-form.select-box 
            name="perPage" 
            onchange="this.form.submit()" 
            :options="collect($perPageOptions)->map(function($value) use ($defaultPerPage) {
                return [
                    'value' => $value,
                    'text' => __('pagination.show') . ' ' . $value,
                    'selected' => request('perPage', $defaultPerPage) == $value
                ];
            })->toArray()"
        />
    </form>
</div> 