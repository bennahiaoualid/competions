<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Enums\DeletionRequestTypeEnum;
use App\Enums\DeletionRequestStatusEnum;
use App\Helpers\DateTimeHelper;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Monitoring\DeletionRequest;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class DeletionRecordsTable extends PowerGridComponent
{
    public string $tableName = 'deletion-records-table-tulvcl-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.deletion_request_detail')
                ->showCollapseIcon()
                ->collapseOthers()
        ];
    }

    public function datasource(): Builder
    {
        if(Auth::user()->hasRole('owner')){
            return DeletionRequest::query()
            ->with(['deletable', 'deletedByAdmin', 'approvedByAdmin']);
        }else{
            return DeletionRequest::query()
            ->where('deletable_type',DeletionRequestTypeEnum::User)
            ->with(['deletable', 'deletedByAdmin', 'approvedByAdmin']);
        }
        
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('deletable_entity', function ($deletionRequest) {
                $name = $deletionRequest->snapshot_name;
                return e($name);
            })

            ->add('deletable_type', function ($deletionRequest) {
                $enum = DeletionRequestTypeEnum::tryFrom($deletionRequest->deletable_type);
                return e($enum?->label() ?? __('Unknown'));
            })

            ->add('requested_by', function ($deletionRequest) {
                $admin = $deletionRequest->deletedByAdmin;
                return $admin ? e($admin->name) : 'Unknown';
            })

            ->add('status', fn ($deletionRequest) => view('components.ui_widgets.status-widget', [
                'status' => $deletionRequest->status,
                'text' => DeletionRequestStatusEnum::from($deletionRequest->status)->label()
            ]))

            ->add('requested_at_local', function ($deletionRequest) {
                return e(DateTimeHelper::toLocalString($deletionRequest->requested_at));
            })

            ->add('approved_by', function ($deletionRequest) {
                $admin = $deletionRequest->approvedByAdmin;
                return $admin ? e($admin->name) : '-';
            })

            ->add('approved_at_local', function ($deletionRequest) {
                return $deletionRequest->approved_at 
                    ? e(DateTimeHelper::toLocalString($deletionRequest->approved_at))
                    : '-';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('deletion.records.entity'), 'deletable_entity', 'snapshot_name')
                ->searchable(),
            Column::make(__('deletion.records.type'), 'deletable_type'),
            Column::make(__('deletion.records.requested_by'), 'requested_by', 'snapshot_deleter_name')
                ->searchable(),
            Column::make(__('deletion.records.status'), 'status'),
            Column::make(__('deletion.records.requested_at'), 'requested_at_local', 'requested_at')
            ->sortable(),
            Column::make(__('deletion.records.approved_by'), 'approved_by'),
            Column::make(__('deletion.records.approved_at'), 'approved_at_local'),
            Column::action(__('form.actions.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', 'status')
                ->dataSource(array_map(function ($status) {
                    return [
                        'id' => $status->value,
                        'name' => $status->label(),
                    ];
                }, DeletionRequestStatusEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('deletable_type', 'deletable_type')
                ->dataSource(array_map(function ($jobType) {
                    return [
                        'id' => $jobType->value,
                        'name' => $jobType->label(),
                    ];
                }, DeletionRequestTypeEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(DeletionRequest $row): array
    {

        $needsAction = $row->deletable ? $row->deletable->trashed() : false;
        $entityName = $row->snapshot_name ;

        return [
            Button::add('reason')
                ->slot(__('deletion.records.reason'))
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-primary border-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary active:text-white focus:ring-primary')
                ->toggleDetail($row->id),

            Button::add('hard_delete_modal')
                ->slot('<i class="fa-solid fa-unlock text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->can($needsAction)
                ->dispatch(
                    'open-modal', 
                        [
                            'detail' => 'hard_delete_admin_modal', 
                            'value' => $row->id,
                            'input_detail' => ['entityName' => $entityName]
                        ]
                        ),

            Button::add('restore_modal')
                ->slot('<i class="fa-solid fa-rotate"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-warning border-warning hover:bg-warning hover:text-white focus:bg-warning focus:text-white active:bg-warning active:text-white focus:ring-warning')
                ->can($needsAction)
                ->dispatch('open-modal', 
                        [
                            'detail' => 'restore_modal', 
                            'value' => $row->id,
                            'input_detail' => ['entityName' => $entityName]
                        ]
                    )
            
        ];
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
} 