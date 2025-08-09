<?php

namespace App\Livewire;

use App\Models\Payment\PaymentTransaction;
use App\Enums\PaymentStatusEnum;
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

final class PaymentUserTransactionTable extends PowerGridComponent
{
    public string $tableName = 'payment-user-transaction-table';

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
        $user = Auth::user();
        
        return PaymentTransaction::query()
            ->where('payable_id', $user->id)
            ->where('payable_type', get_class($user))
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
            ->add('uuid')
            ->add('amount_formatted', function (PaymentTransaction $transaction) {
                return number_format($transaction->amount, 2) . ' DZD';
            })
            ->add('coins_credited')
            ->add('status', function (PaymentTransaction $transaction) {
                $enum = PaymentStatusEnum::tryFrom($transaction->status);
                return view('components.ui_widgets.status-widget', [
                    'status' => $transaction->status,
                    'text' => $enum?->label() ?? ucfirst($transaction->status),
                ]);
            })
            ->add('created_at_formatted', function (PaymentTransaction $transaction) {
                return DateTimeHelper::toLocalString($transaction->created_at);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')
                ->sortable()
                ->searchable(),

            Column::make(__('payment.payment_transaction.fields.amount'), 'amount_formatted', 'amount')
                ->sortable(),

            Column::make(__('payment.payment_transaction.fields.coins_credited'), 'coins_credited')
                ->sortable(),

            Column::make(__('payment.payment_transaction.fields.status'), 'status')
                ->sortable(),

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
        ];
    }

    public function actions(PaymentTransaction $row): array
    {
        return [
            Button::add('view')
                ->slot('<i class="fa-solid fa-eye text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-primary border-primary hover:bg-primary hover:text-white focus:bg-primary focus:text-white active:bg-primary active:text-white focus:ring-primary')
                ->route('payment.transactions.show', ['paymentTransaction' => $row->uuid])
        ];
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
