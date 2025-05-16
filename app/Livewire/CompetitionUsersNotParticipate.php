<?php

namespace App\Livewire;

use App\Models\User;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CompetitionUsersNotParticipate extends PowerGridComponent
{
    public string $tableName = 'competition_users_not_participate';
    public int $competition_id;
    public int $ageMin;
    public int $ageMax;

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
                ->dispatch('bulkDelete.' . $this->tableName, []),
        ];
    }
    #[On('bulkDelete.{tableName}')]
    public function bulkDelete(): void
    {
        $this->js('dispatchEvent(new CustomEvent("open-modal", {
                            detail: { detail: "add_users", value: window.pgBulkActions.get(\'' . $this->tableName . '\')}
                            }))'
        );
    }

    public function datasource(): Builder
    {
        return User::eligibleForCompetition($this->ageMin, $this->ageMax, $this->competition_id);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', function ($user) {
                return sprintf(
                    '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                    route("admin.users.edit",["id" => e($user->id)]),
                    e($user->name)
                );
            })
            ->add('age', function ($user) {
                return e($user->age);
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__("user.profile.name"), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__("user.profile.age"), 'age', 'birthdate')
                ->sortable()
                ->searchable(),
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
