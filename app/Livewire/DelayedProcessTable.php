<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use App\Models\ProcessManagement\DelayedProcess;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use App\Enums\ProcessTypeEnum;

final class DelayedProcessTable extends PowerGridComponent
{
    public string $tableName = 'delayed-process-table-qbvfe3-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::detail()
                ->view('components.tables.process_delayed_detail')
                ->showCollapseIcon()
                ->collapseOthers(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return DelayedProcess::where('initiator_id', Auth::id())
            ->orderByRaw('
                CASE 
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL check_period_hours HOUR) THEN 0 
                    ELSE 1 
                END
            ')
            ->orderByRaw('
                CASE 
                    WHEN created_at <= DATE_SUB(NOW(), INTERVAL check_period_hours HOUR) THEN 
                        -TIMESTAMPDIFF(MINUTE, DATE_ADD(created_at, INTERVAL check_period_hours HOUR), NOW())
                    ELSE 
                        TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(created_at, INTERVAL check_period_hours HOUR))
                END
            ');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('process_type', function ($proccess) {
                $enum = ProcessTypeEnum::tryFrom($proccess->process_type);
                return $enum?->label() ?? __('Unknown');
            })
            ->add('initiator_id')
            ->add('context_data')
            ->add('check_period_hours')
            ->add('created_at')
            ->add('priority')
            ->add('time_minutes')
            ->add('status_field', fn ($proccess) => view('components.ui_widgets.status-widget', [
                'status' => $proccess->status,
                'text' => __('delayed_process.values.status.' . $proccess->status),
            ]))
            ->add('time_display');
    }

    public function columns(): array
    {
        return [            
            Column::make(__('delayed_process.fields.process_type'), 'process_type')
                ->sortable(),

            Column::make(__('delayed_process.fields.target_id'), 'target_id'),

            Column::make(__('delayed_process.fields.status'), 'status_field'),

            Column::make(__('delayed_process.fields.time_display'), 'time_display')
                ->sortable()
                ->searchable(),

            Column::make(__('delayed_process.fields.created_at'), 'created_at')
                ->sortable(),

            Column::action(__('messages.global.action'))
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('process_type', 'process_type')
                ->dataSource(array_map(function ($process) {
                    return [
                        'id' => $process->value,
                        'name' => $process->label(),
                    ];
                }, ProcessTypeEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(DelayedProcess $row): array
    {
        return [
            Button::add('hard_delete_modal')
                ->slot('<i class="fa-solid fa-unlock text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->dispatch(
                    'open-modal', 
                        [
                            'detail' => 'delete_delayed_record_modal', 
                            'value' => $row->id,
                        ]
                    ),
        ];
    }

}
