<?php

namespace App\Livewire;

use App\Models\Competition\Competition;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class CompetitionTable extends PowerGridComponent
{
    use WithExport;

    public function setUp(): array
    {

        return [
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Competition::query()->with("admin");
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('title')
            ->add('created_by', function ($competition) {
                return sprintf(
                    '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                    route("admin.edit",["id" => e($competition->admin->id)]),
                    e($competition->admin->name)
                );
            })
            ->add('start_date', function ($competition) {
                return e(Carbon::parse($competition->start_date)->timezone(session('timezone')));
            })
            ->add('users_age', function ($competition) {
                return e(
                    __("competition.info.from") ." ". $competition->age_start
                    . " " .
                    __("competition.info.to") ." ". $competition->age_end
                );
            })
            ->add('levels_number')
            ->add('active', function ($competition) {
                return Blade::render(
                    '<x-status-widget status="'. $competition->getStatus().
                    '" text="'.__('competition.info.status.'.$competition->getStatus()).'" />'
                );
            });

    }

    public function columns(): array
    {
        return [
            Column::make(__('competition.info.title'), 'title')
                ->sortable()
                ->searchable(),
            Column::make(__('competition.info.created_by'), 'created_by'),
            Column::make(__('competition.info.start_date'), 'start_date')
                ->sortable(),
            Column::make(__('competition.info.users_age'), 'users_age'),
            Column::make(__('competition.info.levels_number'), 'levels_number'),
            Column::make(__('competition.info.status.state'), 'active'),
            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function actions(Competition $row): array
    {
        return [
            Button::add('edit')
                ->slot('<i class="fa-regular fa-pen-to-square"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-info border-info hover:bg-info hover:text-white focus:bg-info focus:text-white active:bg-info active:text-white focus:ring-info')
                ->route('admin.competitions.edit', ['id' =>base64_encode( $row->id)]),
        ];
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
