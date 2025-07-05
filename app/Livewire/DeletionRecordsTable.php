<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Enums\DeletionRequestTypeEnum;
use App\Enums\DeletionRequestStatusEnum;
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
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
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
                $deletable = $deletionRequest->deletable;
                if (!$deletable) {
                    return '<span class="text-gray-500">Entity not found</span>';
                }
                
                $name = $deletionRequest->snapshot_name ?? $deletable->name ?? 'Unknown';
                
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

            ->add('reason', function ($deletionRequest) {
                return e($deletionRequest->reason);
            })

            ->add('status', fn ($deletionRequest) => view('components.ui_widgets.status-widget', [
                'status' => $deletionRequest->status,
                'text' => DeletionRequestStatusEnum::from($deletionRequest->status)->label()
            ]))

            ->add('requested_at', function ($deletionRequest) {
                return e(Carbon::parse($deletionRequest->requested_at)->format('Y-m-d H:i:s'));
            })

            ->add('approved_by', function ($deletionRequest) {
                $admin = $deletionRequest->approvedByAdmin;
                return $admin ? e($admin->name) : '-';
            })

            ->add('approved_at', function ($deletionRequest) {
                return $deletionRequest->approved_at ;
                   /* ? e(Carbon::parse($deletionRequest->approved_at)->format('Y-m-d H:i:s'))
                    : '-';*/
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('deletion.records.entity'), 'deletable_entity')
                ->sortable()
                ->searchable(),
            Column::make(__('deletion.records.type'), 'deletable_type')
                ->sortable()
                ->searchable(),
            Column::make(__('deletion.records.requested_by'), 'requested_by')
                ->sortable()
                ->searchable(),
            Column::make(__('deletion.records.reason'), 'reason')
                ->searchable(),
            Column::make(__('deletion.records.status'), 'status')
                ->sortable(),
            Column::make(__('deletion.records.requested_at'), 'requested_at')
                ->sortable(),
            Column::make(__('deletion.records.approved_by'), 'approved_by')
                ->sortable(),
            /*Column::make(__('deletion.records.approved_at'), 'approved_at')
                ->sortable(),*/
            Column::action(__('form.actions.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            // Add filters if needed
        ];
    }

    public function actions(DeletionRequest $row): array
    {
        $actions = [];
        
        // Get entity details for modal display

        $needsAction = $row->deletable ? $row->deletable->trashed() : false;
        $entityName = $row->snapshot_name ;
        $actions[] = Button::add('hard_delete_modal')
            ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
            ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
        bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
            ->dispatch('open-modal', ['detail' => 'hard_delete_modal', 'value' => $row->id]);
        
        // Check if user can perform actions on this specific deletable type
        /*if ($row->deletable_type === User::class && Auth::user()->can('hard delete user')) {
            $actions[] = Button::add('hard-delete')
                ->slot('<i class="fa-solid fa-trash"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-red-600 border-red-600 hover:bg-red-600 hover:text-white focus:bg-red-600 focus:text-white active:bg-red-600 active:text-white focus:ring-red-600')
                ->dispatch('openHardDeleteModal', [
                    'id' => $row->id,
                    'entityName' => $entityName,
                    'entityType' => $entityType
                ]);
        }
        
        /*if ($row->deletable_type === Admin::class && Auth::user()->can('hard delete admin')) {
            $actions[] = Button::add('hard-delete')
                ->slot('<i class="fa-solid fa-trash"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-red-600 border-red-600 hover:bg-red-600 hover:text-white focus:bg-red-600 focus:text-white active:bg-red-600 active:text-white focus:ring-red-600')
                ->dispatch('openHardDeleteModal', [
                    'id' => $row->id,
                    'entityName' => $entityName,
                    'entityType' => $entityType
                ]);
        }*/
        
        // Restore action (available for both types if user has general restore permission)
        /*if (Auth::user()->can('restore deleted entities')) {
            $actions[] = Button::add('restore')
                ->slot('<i class="fa-solid fa-undo"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-1 text-lg bg-transparent text-green-600 border-green-600 hover:bg-green-600 hover:text-white focus:bg-green-600 focus:text-white active:bg-green-600 active:text-white focus:ring-green-600')
                ->dispatch('openRestoreModal', [
                    'id' => $row->id,
                    'entityName' => $entityName,
                    'entityType' => $entityType
                ]);
        }*/
        
        return [
            Button::add('hard_delete_modal')
                ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->can($needsAction)
                ->dispatch('open-modal', 
                        [
                            'detail' => 'hard_delete_modal', 
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