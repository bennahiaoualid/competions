<?php

namespace App\Livewire;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CompetitionAdminNotAudit extends PowerGridComponent
{
    public Competition $competition;
    public string $tableName = 'competition_admin_not_audit';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('bulk-delete')
                ->slot('<i class="fa-solid fa-plus me-2"></i>'. __('form.actions.add') . ' (<span x-text="window.pgBulkActions.count(\'' . $this->tableName . '\')"></span>)')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-4 py-2 text-xs bg-primary text-white border-transparent hover:bg-primary-dark focus:bg-primary-dark active:bg-primary-dark focus:ring-primary')
                ->can($this->competition->canEdit())
                ->dispatch('bulkDelete.' . $this->tableName, [])
        ];
    }
    #[On('bulkDelete.{tableName}')]
    public function bulkDelete(): void
    {
        $this->js('dispatchEvent(new CustomEvent("open-modal", {
                            detail: { detail: "add_auditors", value: window.pgBulkActions.get(\'' . $this->tableName . '\')}
                            }))'
        );
    }
    public function datasource(): Builder
    {
        return Admin::query()
            ->withCount('competitionsAudit') // Eager load the count of competitionsAudit
            ->whereDoesntHave('competitionsAudit', function ($q) {
                $q->where('Competitions.id', $this->competition->id);
            });
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
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
            ->add('auditing_in', function ($admin) {
                return e($admin->competitions_audit_count); // Use the eager-loaded count
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
        ];
    }

    public function filters(): array
    {
        return [

        ];
    }


    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
