<?php

namespace App\Livewire;

use App\Enums\UserTypeEnum;
use App\Helpers\DateTimeHelper;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Auth;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CoinPricingTable extends PowerGridComponent
{
    public string $tableName = 'coin-pricing-table';

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
        return CoinPricing::query()
            ->with('createdByAdmin')
            ->orderBy('created_at', 'desc');
    }

    public function relationSearch(): array
    {
        return [
            'createdByAdmin' => [
                'name',
                'email'
            ]
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('user_type', function (CoinPricing $pricing) {
                $enum = UserTypeEnum::tryFrom($pricing->user_type);
                return e($enum?->label() ?? ucfirst($pricing->user_type));
            })
            ->add('rate_description', function (CoinPricing $pricing) {
                return $pricing->rate_description;
            })
            ->add('coins_per_dzd', function (CoinPricing $pricing) {
                return number_format($pricing->coins_per_dzd, 2) . ' coins/DZD';
            })
            ->add('status', function (CoinPricing $pricing) {
                $status = $pricing->is_active ? 'active' : 'disabled';
                return view('components.ui_widgets.status-widget', [
                    'status' => $status,
                    'text' => __('payment.pricing.status.' . $status),
                ]);
            })
            ->add('is_active')
            ->add('created_by_admin_name', function (CoinPricing $pricing) {
                return $pricing->createdByAdmin?->name ?? 'N/A';
            })
            ->add('created_at_formatted', function (CoinPricing $pricing) {
                return DateTimeHelper::toLocalString($pricing->created_at);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')
                ->sortable()
                ->searchable(),

            Column::make(__('payment.pricing.fields.user_type'), 'user_type')
                ->sortable(),

            Column::make(__('payment.pricing.fields.rate'), 'rate_description')
                ->sortable(),

            Column::make(__('payment.pricing.fields.coins_per_dzd'), 'coins_per_dzd')
                ->sortable(),

            Column::make(__('payment.pricing.fields.status'), 'status')
                ->sortable(),

            Column::make(__('payment.pricing.fields.created_by'), 'created_by_admin_name')
                ->sortable(),

            Column::make(__('payment.pricing.fields.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(__('payment.pricing.fields.actions'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('user_type', 'user_type')
                ->dataSource(UserTypeEnum::options())
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('status', 'is_active')
                ->dataSource([
                    ['id' => '1', 'name' => __('payment.pricing.status.active')],
                    ['id' => '0', 'name' => __('payment.pricing.status.inactive')],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(CoinPricing $row): array
    {
        $actions = [];
        if (Auth::user()->can('manage coin_pricing')) {
            // Delete action
            $actions[] = Button::add('delete')
                ->slot('<i class="fa-solid fa-trash text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-red-600 border-red-600 hover:bg-red-600 hover:text-white focus:bg-red-600 focus:text-white active:bg-red-600 active:text-white focus:ring-red-600')
                ->dispatch('open-modal', ['detail' => 'delete-pricing-modal', 'value' => $row->id]);

            // Activate/Deactivate actions
            if ($row->is_active) {
                $actions[] = Button::add('deactivate')
                    ->slot('<i class="fa-solid fa-pause text-base"></i>')
                    ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                    bg-transparent text-yellow-600 border-yellow-600 hover:bg-yellow-600 hover:text-white focus:bg-yellow-600 focus:text-white active:bg-yellow-600 active:text-white focus:ring-yellow-600')
                    ->dispatch('open-modal', ['detail' => 'deactivate-pricing-modal', 'value' => $row->id]);
            } else {
                $actions[] = Button::add('activate')
                    ->slot('<i class="fa-solid fa-play text-base"></i>')
                    ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                    bg-transparent text-green-600 border-green-600 hover:bg-green-600 hover:text-white focus:bg-green-600 focus:text-white active:bg-green-600 active:text-white focus:ring-green-600')
                    ->dispatch('open-modal', ['detail' => 'activate-pricing-modal', 'value' => $row->id]);
            }
        }

        return $actions;
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
