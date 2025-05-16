<?php

namespace App\Livewire;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable; 

final class ActivityTable extends PowerGridComponent
{
    use WithExport;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable()
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV), 
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Activity::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('log_name')
            ->add('subject', function ($act) {
                return sprintf(
                    '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
                    href="%s">%s</a>',
                    route("admin.edit",["id" => e($act->subject_id)]),
                    e($act->subject->name)
                );
            })
            ->add('causer', function ($act) {
                return sprintf(
                    '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800 visited:text-purple-600"
                    href="%s">%s</a>',
                    route("admin.edit",["id" => e($act->causer_id)]),
                    e($act->causer->name)
                );
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('log name', 'log_name')
                ->sortable(),
            Column::make('subject', 'subject')
                ->sortable(),
            Column::make('causer', 'causer')
                ->sortable(),

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
}
