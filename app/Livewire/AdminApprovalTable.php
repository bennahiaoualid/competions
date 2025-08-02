<?php

namespace App\Livewire;

use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use App\Helpers\DateTimeHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use App\PowerGridThemes\TailwindStriped;

final class AdminApprovalTable extends PowerGridComponent
{
    public string $tableName = 'admin-approval-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return AdminApproval::query()
            ->with(['admin', 'entity'])
            ->where('admin_id', Auth::id())
            ->orderBy('created_at', 'desc');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('admin_name', function (AdminApproval $approval) {
                return e($approval->admin->name);
            })
            ->add('entity_type', function (AdminApproval $approval) {
                $type = class_basename($approval->entity_type);
                return e($type);
            })
            ->add('type', function (AdminApproval $approval) {
                $enum = AdminApprovalTypeEnum::tryFrom($approval->type);
                return e($enum?->label() ?? ucfirst($approval->type));
            })
            ->add('status', function (AdminApproval $approval) {
                return view('components.ui_widgets.status-widget', [
                    'status' => $approval->status,
                    'text' => __('admin.admin_approval.status.' . $approval->status)
                ]);
            })
            ->add('created_at_formatted', function (AdminApproval $approval) {
                return e(DateTimeHelper::toLocalString($approval->created_at));
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('admin.admin_approval.fields.admin'), 'admin_name', 'admin.name')
                ->sortable()
                ->searchable(),

            Column::make(__('admin.admin_approval.fields.entity'), 'entity_type')
                ->sortable()
                ->searchable(),

            Column::make(__('admin.admin_approval.fields.type'), 'type')
                ->sortable()
                ->searchable(),

            Column::make(__('admin.admin_approval.fields.status'), 'status')
                ->sortable(),

            Column::make(__('admin.admin_approval.fields.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(__('admin.admin_approval.fields.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', 'status')
                ->dataSource([
                    ['id' => 'pending', 'name' => __('admin.admin_approval.status.pending')],
                    ['id' => 'approved', 'name' => __('admin.admin_approval.status.approved')],
                    ['id' => 'rejected', 'name' => __('admin.admin_approval.status.rejected')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('type', 'type')
                ->dataSource(array_map(function ($type) {
                    return [
                        'id' => $type->value,
                        'name' => $type->label(),
                    ];
                }, AdminApprovalTypeEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(AdminApproval $row): array
    {
        $actions = [];

        // Add View Details action for all requests
        $enum = AdminApprovalTypeEnum::tryFrom($row->type);
        if ($enum) {
            $routeName = $enum->getDetailUrl($row->entity_id)['name'];
            $routeParams = $enum->getDetailUrl($row->entity_id)['params'];
            $actions[] = Button::add('view_details')
                ->slot('<i class="fa-solid fa-eye text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-primary border-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary active:text-white focus:ring-primary')
                ->route($routeName, $routeParams, '_blank');
        }

        // Only show approve/reject for pending requests
        if ($row->status === 'pending') {
            $actions[] = Button::add('approve')
                ->slot('<i class="fa-solid fa-check text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-success border-success hover:bg-success hover:text-white focus:bg-success focus:text-white active:bg-success active:text-white focus:ring-success')
                ->dispatch('open-modal', ['detail' => 'approve-approval-modal', 'value' => $row->id]);

            $actions[] = Button::add('reject')
                ->slot('<i class="fa-solid fa-xmark text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->dispatch('open-modal', ['detail' => 'reject-approval-modal', 'value' => $row->id]);
        }elseif($row->created_at->diffInDays(now()) > 1){
           // Show delete for all requests
            $actions[] = Button::add('delete_approval')
                        ->slot('<i class="fa-solid fa-trash text-base"></i>')
                        ->class('px-2 py-1  inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                    bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                        ->dispatch('open-modal', ['detail' => 'delete-approval-modal', 'value' => $row->id]);

        }

        
        return $actions;
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
