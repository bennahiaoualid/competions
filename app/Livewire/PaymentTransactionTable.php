<?php

namespace App\Livewire;

use App\Models\Payment\PaymentTransaction;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Helpers\DateTimeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use App\PowerGridThemes\TailwindStriped;

final class PaymentTransactionTable extends PowerGridComponent
{
    public string $tableName = 'payment-transaction-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.payment.transaction-detail-row')
                ->showCollapseIcon()
                ->collapseOthers()
        ];
    }

    public function datasource(): Builder
    {
        return PaymentTransaction::query()
            ->with(['payable', 'approver'])
            ->orderBy('created_at', 'desc');
    }

    public function relationSearch(): array
    {
        return [
            'payable' => [
                'name',
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('uuid')
            ->add('payer_name', function (PaymentTransaction $transaction) {
                return e($transaction->payable?->name ?? 'N/A');
            })
            ->add('payer_type', function (PaymentTransaction $transaction) {
                $type = PaymentTypeEnum::tryFrom($transaction->payable_type);
                return e($type?->label() ?? 'N/A');
            })
            ->add('amount_formatted', function (PaymentTransaction $transaction) {
                return number_format($transaction->amount, 2) . ' DZD';
            })
            ->add('coins_credited')
            ->add('payment_method', function (PaymentTransaction $transaction) {
                return __('payment.payment_transaction.payment_method.' . $transaction->payment_method);
            })
            ->add('status', function (PaymentTransaction $transaction) {
                $enum = PaymentStatusEnum::tryFrom($transaction->status);
                return view('components.ui_widgets.status-widget', [
                    'status' => $transaction->status,
                    'text' => $enum?->label() ?? ucfirst($transaction->status),
                ]);
            })
            ->add('approver_name', function (PaymentTransaction $transaction) {
                return e($transaction->approver?->name ?? 'N/A');
            })
            ->add('approved_at_formatted', function (PaymentTransaction $transaction) {
                return $transaction->approved_at ? DateTimeHelper::toLocalString($transaction->approved_at) : 'N/A';
            })
            ->add('created_at_formatted', function (PaymentTransaction $transaction) {
                return DateTimeHelper::toLocalString($transaction->created_at);
            })
            ->add('accountant_observation', function (PaymentTransaction $transaction) {
                return e($transaction->accountant_observation ?? 'N/A');
            })
            ->add('proof_image_url', function (PaymentTransaction $transaction) {
                return $transaction->proof_image_path
                    ? route('transactions.proof', $transaction->id)
                    : null;
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('payment.payment_transaction.fields.transaction_id'), 'uuid')
                ->searchable(),

            Column::make(__('payment.payment_transaction.fields.payer'), 'payer_name', 'payable.name'),

            Column::make(__('payment.payment_transaction.fields.amount'), 'amount_formatted', 'amount'),

            Column::make(__('payment.payment_transaction.fields.status'), 'status'),

            Column::make(__('payment.payment_transaction.fields.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(__('payment.payment_transaction.fields.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', 'status')
                ->dataSource([
                    ['id' => 'pending', 'name' => __('payment.payment_transaction.status.pending')],
                    ['id' => 'approved', 'name' => __('payment.payment_transaction.status.approved')],
                    ['id' => 'rejected', 'name' => __('payment.payment_transaction.status.rejected')],
                    ['id' => 'cancelled', 'name' => __('payment.payment_transaction.status.cancelled')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('payment_method', 'payment_method')
                ->dataSource([
                    ['id' => 'cash', 'name' => __('payment.payment_transaction.payment_method.cash')],
                    ['id' => 'bank_transfer', 'name' => __('payment.payment_transaction.payment_method.bank_transfer')],
                    ['id' => 'mobile_money', 'name' => __('payment.payment_transaction.payment_method.mobile_money')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(PaymentTransaction $row): array
    {
        $actions = [];
        $authUser = Auth::user();

        // Only show approve/reject/cancel for pending transactions
        $statusEnum = PaymentStatusEnum::tryFrom($row->status);
        if ($statusEnum && $statusEnum->canApprove() && $authUser->can('manage payment')) {
            $actions[] = Button::add('approve')
                ->slot('<i class="fa-solid fa-check text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-success border-success hover:bg-success hover:text-white focus:bg-success focus:text-white active:bg-success active:text-white focus:ring-success')
                ->dispatch('open-modal', ['detail' => 'approve-payment-modal', 'value' => $row->uuid]);

            $actions[] = Button::add('reject')
                ->slot('<i class="fa-solid fa-xmark text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->dispatch('open-modal', ['detail' => 'reject-payment-modal', 'value' => $row->uuid]);

            $actions[] = Button::add('cancel')
                ->slot('<i class="fa-solid fa-ban text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-warning border-warning hover:bg-warning hover:text-white focus:bg-warning focus:text-white active:bg-warning active:text-white focus:ring-warning')
                ->dispatch('open-modal', ['detail' => 'cancel-payment-modal', 'value' => $row->uuid]);
        }

        return $actions;
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }

}
