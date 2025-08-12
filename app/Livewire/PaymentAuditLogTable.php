<?php

namespace App\Livewire;

use App\Models\Payment\PaymentAuditLog;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class PaymentAuditLogTable extends PowerGridComponent
{
    public string $tableName = 'payment-audit-log-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.payment.audit-log-detail-row')
                ->showCollapseIcon()
                ->collapseOthers()
        ];
    }

    public function datasource(): Builder
    {
        return PaymentAuditLog::query()
            ->with(['paymentTransaction.payable', 'admin'])
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
            ->add('transaction_uuid', function (PaymentAuditLog $log) {
                return e($log->paymentTransaction?->uuid ?? 'N/A');
            })
            ->add('payer_name', function (PaymentAuditLog $log) {
                return e($log->paymentTransaction?->payable?->name ?? 'N/A');
            })
            ->add('action')
            ->add('admin_name', function (PaymentAuditLog $log) {
                return e($log->admin?->name ?? 'N/A');
            })
            ->add('created_at_formatted', function (PaymentAuditLog $log) {
                return $log->created_at?->format('Y-m-d H:i');
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('payment.audit.columns.id'), 'id')
                ->sortable(),

            Column::make(__('payment.audit.columns.transaction_uuid'), 'transaction_uuid')
                ->searchable()
                ->sortable(),

            Column::make(__('payment.audit.columns.payer'), 'payer_name')
                ->searchable()
                ->sortable(),

            Column::make(__('payment.audit.columns.action'), 'action')
                ->searchable()
                ->sortable(),

            Column::make(__('payment.audit.columns.admin'), 'admin_name')
                ->searchable()
                ->sortable(),

            Column::make(__('payment.audit.columns.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(__('payment.audit.columns.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('action', 'action')
                ->dataSource([
                    ['id' => 'created', 'name' => __('payment.audit.actions.created')],
                    ['id' => 'approved', 'name' => __('payment.audit.actions.approved')],
                    ['id' => 'rejected', 'name' => __('payment.audit.actions.rejected')],
                    ['id' => 'modified', 'name' => __('payment.audit.actions.modified')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(PaymentAuditLog $row): array
    {
        return [
            Button::add('delete')
                ->slot('<i class="fa-solid fa-trash text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->dispatch('open-modal', ['detail' => 'delete-audit-log-modal', 'value' => $row->id]),
        ];
    }
}
