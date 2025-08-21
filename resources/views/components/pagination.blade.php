@props(['paginator', 'perPageOptions' => [5, 10, 25, 50, 100], 'defaultPerPage' => 5])

<div class="flex justify-end items-center gap-4 mt-4 flex-wrap">
    {{-- Laravel Pagination --}}
    {{ $paginator->appends(request()->query())->links() }}

        {{-- Per Page Dropdown --}}
        <form method="GET">
            @foreach(request()->query() as $key => $value)
                @if($key !== 'perPage')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
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