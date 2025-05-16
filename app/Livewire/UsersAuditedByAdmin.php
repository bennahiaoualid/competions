<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UsersAuditedByAdmin extends PowerGridComponent
{
    public int $admin_id ;
    public int $level_id ;
    public string $primaryKey = 'users.id';
    public string $tableName = 'UsersAuditedByAdmin';
    public function setUp(): array
    {

        return [

            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return User::query()
            ->select('users.id', 'users.anonymized_identifier')

            ->withCount(['responses as all_audited' => function ($query) {
                $query->whereNull('admin_id');
            }])
            ->whereHas('levelAdminUser', function ($query) {
                $query->where('level_admin_user.admin_id', $this->admin_id)
                    ->where('level_id', $this->level_id);
            })
            ->whereHas('responses.question', function ($query) {
                $query->where('level_id', $this->level_id);
            });
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('anonymized_identifier')
            ->add('all_audited',function ($user){
                if ($user->all_audited == 0){
                    return sprintf('<span class="px-2 py-0.5 bg-green-400 rounded-lg">%s</span>',e(__('competition.info.auditor.all_audited')));
                }else{
                    return sprintf('<span class="px-2 py-0.5 bg-red-300 rounded-lg">%s</span>',e(__('competition.info.auditor.not_audited')));
                }
            });

    }

    public function columns(): array
    {
        return [
            Column::make(__('competition.info.competitors'), 'anonymized_identifier'),
            Column::make(__('competition.info.status.state'), 'all_audited')
            ->sortable(),

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

    public function actions(User $row): array
    {
        return [
            Button::add('edit')
                ->slot('View')
                ->class('btn btn-primary')
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->route('admin.auditor.users.responses', ['user_id' => $row->anonymized_identifier,'level_id' => $this->level_id]),
        ];
    }

}
