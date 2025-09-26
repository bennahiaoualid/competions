<?php

namespace App\Livewire;

use App\Helpers\DateTimeHelper;
use Illuminate\Support\Facades\Blade;
use App\Models\Competition\Competition;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CompetitionTable extends PowerGridComponent
{
    public string $tableName = 'competition_table';
    public string $sortField = 'start_date';
    public string $sortDirection = 'desc';

    public function setUp(): array
    {

        return [
            PowerGrid::header()
            ->showSearchInput(),

            PowerGrid::footer()
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
                if($competition->admin){
                    return sprintf(
                        '<a target="_blank"
                        class="underline text-blue-600 hover:text-blue-800"
                        href="%s">%s</a>',
                        route("admin.edit",["id" => e($competition->admin->id)]),
                        e($competition->admin->name)
                    );
                }
                
            })
            ->add('start_date_local', function ($competition) {
                return DateTimeHelper::toLocalDateTime($competition->start_date);
            })
            ->add('users_age', function ($competition) {
                return e(
                    __("competition.info.from") ." ". $competition->age_start
                    . " " .
                    __("competition.info.to") ." ". $competition->age_end
                );
            })
            ->add('status', function ($competition) {
                return Blade::render(
                    '<x-status-widget status="'. $competition->status.
                    '" text="'.__('competition.info.status.'.$competition->status).'" />'
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
            Column::make(__('competition.info.start_date'), 'start_date_local','start_date')
                ->sortable(),
            Column::make(__('competition.info.users_age'), 'users_age'),
            Column::make(__('competition.info.status.state'), 'status'),
            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('title', 'title')
            ->operators(['starts_with']),
        ];
    }


    public function actions(Competition $row): array
    {
        return [
            Button::add('edit')
                ->slot('<i class="fa-regular fa-pen-to-square"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-info border-info hover:bg-info hover:text-white focus:bg-info focus:text-white active:bg-info active:text-white focus:ring-info')
                ->route('admin.competitions.edit', ['id' =>base64_encode( $row->id)]),

            Button::add('delete_competitions')
                ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
                ->class('px-2 py-1  inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->can($row->canEdit())
                ->dispatch('open-modal', ['detail' => 'delete', 'value' => $row->id]),
        ];
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
