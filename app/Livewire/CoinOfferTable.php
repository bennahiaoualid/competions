<?php

namespace App\Livewire;

use App\Helpers\DateTimeHelper;
use App\Models\Payment\CoinOffer;
use Illuminate\Support\Facades\Auth;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CoinOfferTable extends PowerGridComponent
{
    public string $tableName = 'coin-offer-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.payment.offer-detail-row')
                ->showCollapseIcon()
                ->collapseOthers()
        ];
    }

    public function datasource(): Builder
    {
        return CoinOffer::query()
            ->with(['createdByAdmin', 'coinPricing'])
            ->orderBy('created_at', 'desc');
    }

    public function relationSearch(): array
    {
        return [
            'createdByAdmin' => [
                'name',
                'email'
            ],
            'coinPricing' => [
                'user_type'
            ]
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('row_number', function ($row) {
                $perPage = $this->perPage ?? 10;
                $currentPage = request()->get('page', 1);
                static $counter = ($currentPage - 1) * $perPage;
                return ++$counter;
            })
            ->add('name')
            ->add('description')
            ->add('discount_percentage', function (CoinOffer $offer) {
                return $offer->discount_percentage . '%';
            })
            ->add('pricing_info', function (CoinOffer $offer) {
                return $offer->coinPricing?->rate_description ?? 'N/A';
            })
            ->add('user_type')
            ->add('date_range', function (CoinOffer $offer) {
                return DateTimeHelper::toLocalString($offer->start_date) . ' - ' . DateTimeHelper::toLocalString($offer->end_date);
            })
            ->add('status_widget', function (CoinOffer $offer) {
                return view('components.ui_widgets.status-widget', [
                    'status' => $offer->status,
                    'text' => __('payment.offers.status.' . $offer->status),
                ]);
            })
            ->add('expired')
            ->add('created_by_admin_name', function (CoinOffer $offer) {
                return $offer->createdByAdmin?->name ?? 'N/A';
            })
            ->add('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'row_number'),

            Column::make(__('payment.offers.fields.name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(__('payment.offers.fields.discount_percentage'), 'discount_percentage'),

            Column::make(__('payment.offers.fields.pricing'), 'pricing_info'),

            Column::make(__('payment.offers.fields.status'), 'status_widget'),

            Column::action(__('payment.offers.fields.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_widget', 'expired')
                ->dataSource([
                    ['id' => '0', 'name' => __('payment.offers.status.active')],
                    ['id' => '1', 'name' => __('payment.offers.status.expired')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(CoinOffer $row): array
    {
        $actions = [];
        
        if (Auth::user()->can('manage payment_offer')) {
            // Delete action
            $actions[] = Button::add('delete')
                ->slot('<i class="fa-solid fa-trash text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-red-600 border-red-600 hover:bg-red-600 hover:text-white focus:bg-red-600 focus:text-white active:bg-red-600 active:text-white focus:ring-red-600')
                ->dispatch('open-modal', ['detail' => 'delete-offer-modal', 'value' => $row->id]);

        }

        return $actions;
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
