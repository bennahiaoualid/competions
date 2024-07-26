<?php

namespace App\Livewire;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

final class ActivityTable extends PowerGridComponent
{
    use WithExport;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
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

   /* public function actions(Activity $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit: '.$row->id)
                ->id()
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->dispatch('edit', ['rowId' => $row->id])
        ];
    }*/

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
