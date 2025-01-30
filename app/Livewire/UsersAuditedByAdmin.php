<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class UsersAuditedByAdmin extends PowerGridComponent
{
    public int $admin_id ;
    public int $level_id ;
    public string $primaryKey = 'users.id';
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
       /* return User::query()
            ->join('level_admin_user', 'users.id', '=', 'level_admin_user.user_id')
            ->leftJoin('responses', 'users.id', '=', 'responses.user_id')
            ->leftJoin('questions', 'responses.question_id', '=', 'questions.id')
            ->where('level_admin_user.admin_id', $this->admin_id)
            ->where('level_admin_user.level_id', $this->level_id)
            ->where('questions.level_id', $this->level_id)
            ->orderBy('users.id')
            ->select('users.id', 'users.anonymized_identifier')
            ->selectRaw('
                    CASE
                        WHEN COUNT(responses.id) > 0 AND SUM(CASE WHEN responses.admin_id IS NULL THEN 1 ELSE 0 END) = 0 THEN 1
                        ELSE 0
                    END AS all_audited
                ')
            ->groupBy('users.id', 'users.anonymized_identifier') ;*/
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

    /*
    public function actionRules(User $row): array
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
