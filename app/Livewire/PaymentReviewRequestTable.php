<?php

namespace App\Livewire;

use App\Helpers\DateTimeHelper;
use App\Models\Payment\PaymentReviewRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use App\PowerGridThemes\TailwindStriped;

final class PaymentReviewRequestTable extends PowerGridComponent
{
    public string $tableName = 'payment-review-request-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.payment.review-detail-row')
                ->showCollapseIcon()
                ->collapseOthers(),
        ];
    }

    public function datasource(): Builder
    {
        return PaymentReviewRequest::query()
            ->with(['paymentTransaction.payable', 'reviewer'])
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
            ->add('transaction_uuid', function (PaymentReviewRequest $review) {
                return e($review->paymentTransaction?->uuid ?? 'N/A');
            })
            ->add('payer_name', function (PaymentReviewRequest $review) {
                return e($review->paymentTransaction?->payable?->name ?? 'N/A');
            })
            ->add('status_widget', function (PaymentReviewRequest $review) {
                $status = $review->status;
                return view('components.ui_widgets.status-widget', [
                    'status' => $status,
                    'text' => __('payment.review.status.' . $status),
                ]);
            })
            ->add('created_at_formatted', function (PaymentReviewRequest $review) {
                return DateTimeHelper::toLocalString($review->created_at);
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('payment.review.columns.transaction_uuid'), 'transaction_uuid')
                ->sortable()
                ->searchable(),

            Column::make(__('payment.review.columns.payer'), 'payer_name')
                ->sortable()
                ->searchable(),

            Column::make(__('payment.review.columns.status'), 'status_widget')
                ->sortable(),

            Column::make(__('payment.review.columns.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(__('payment.review.columns.actions')),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_widget', 'status')
                ->dataSource([
                    ['id' => 'pending', 'name' => __('payment.review.status.pending')],
                    ['id' => 'approved', 'name' => __('payment.review.status.approved')],
                    ['id' => 'rejected', 'name' => __('payment.review.status.rejected')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(PaymentReviewRequest $row): array
    {
        $actions = [];

        if (Auth::user()->can('manage payment') && $row->status === 'pending') {
            $actions[] = Button::add('approve')
                ->slot('<i class="fa-solid fa-check text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 bg-transparent text-success border-success hover:bg-success hover:text-white focus:bg-success focus:text-white active:bg-success active:text-white focus:ring-success')
                ->dispatch('open-modal', ['detail' => 'approve-review-modal', 'value' => $row->id]);

            $actions[] = Button::add('reject')
                ->slot('<i class="fa-solid fa-xmark text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->dispatch('open-modal', ['detail' => 'reject-review-modal', 'value' => $row->id]);
        }

        return $actions;
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
