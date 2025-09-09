<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Competition\Level;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class UserResponseAuditingTable extends PowerGridComponent
{
    public string $tableName = 'user-response-auditing-table';
    public int $admin_id;
    public Level $level;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::cache()
            ->ttl(60) 
            ->customTag('users_responses_auditing_system_'.$this->admin_id),
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return User::query()
            ->select('users.id', 'users.anonymized_identifier')
            ->withCount([
                'responses as all_audited' => function ($query) {
                    $query->whereNull('admin_id')
                          ->whereHas('question', function ($q) {
                              $q->where('level_id', $this->level->id);
                          });
                },
                'responses as ai_generated_count' => function ($query) {
                    $query->whereNull('admin_id')
                          ->where('ai_generated', true)
                          ->whereHas('question', function ($q) {
                              $q->where('level_id', $this->level->id);
                          });
                }
            ])
            ->whereHas('levelAdminUser', function ($query) {
                $query->where('level_admin_user.admin_id', $this->admin_id)
                      ->where('level_id', $this->level->id);
            })
            ->whereHas('responses.question', function ($query) {
                $query->where('level_id', $this->level->id);
            });
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
                ->add('anonymized_identifier')
                ->add('audit_status', function ($user) {
                    if ($user->all_audited == 0) {
                        return sprintf('<span class="px-2 py-0.5 bg-green-400 rounded-lg">%s</span>', 
                            e(__('competition.info.auditor.all_audited')));
                    } elseif ($this->level->competition->ai_auditing && $user->ai_generated_count > 0) {
                        return sprintf('<span class="px-2 py-0.5 bg-yellow-400 rounded-lg">%s</span>', 
                            e(__('competition.info.auditor.needs_confirmation')));
                    } else {
                        return sprintf('<span class="px-2 py-0.5 bg-red-300 rounded-lg">%s</span>', 
                            e(__('competition.info.auditor.not_audited')));
                    }
                });
    }

    public function columns(): array
    {
        return [
            Column::make(__('competition.info.competitors'), 'anonymized_identifier'),
            Column::make(__('competition.info.status.state'), 'audit_status')
            ->sortable(),
            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            
        ];
    }

    public function actions(User $row): array
    {
        return [
            Button::add('edit')
            ->slot('<i class="fa-regular fa-pen-to-square"></i>')
            ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-info border-info hover:bg-info hover:text-white focus:bg-info focus:text-white active:bg-info active:text-white focus:ring-info')
            ->route('admin.auditor.users.responses', ['user_id' => $row->anonymized_identifier,'level' => $this->level])
        ];
    }


    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
