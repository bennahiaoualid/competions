<?php

namespace App\Livewire;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\PowerGridThemes\TailwindStriped;
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

final class CompetitionAuditorsTable extends PowerGridComponent
{
    public int $competition;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Admin::query()->whereHas("competitionsAudit",function ($q){
            $q->where('Competitions.id', $this->competition);
        })->with("competitionsAudit");
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name', function ($admin) {
                return sprintf(
                    '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                    route("admin.users.edit",["id" => e($admin->id)]),
                    e($admin->name)
                );
            })
            ->add('email')
            ->add('auditing_in',function ($admin){
                return e($admin->competitionsAudit->count());
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__("user.profile.name"), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__("user.profile.email"), 'email')
                ->sortable()
                ->searchable(),
            Column::make(__("competition.info.auditor.in_competition"), 'auditing_in'),

            Column::action('')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datepicker('birthdate'),
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function actions(Admin $row): array
    {
        $competition = Competition::find($this->competition);
        if ($competition && $competition->canEdit()){
            return [
                Button::add('delete_auditor')
                    ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
                    ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')

                    ->dispatch('open-modal', ['detail' => 'delete', 'value' => $row->id]),
            ];
        }
        return [

        ];
    }

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
    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
