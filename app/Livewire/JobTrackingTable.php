<?php

namespace App\Livewire;

use Auth;
use Carbon\Carbon;
use App\Enums\JobTypeEnum;
use App\Enums\JobStatusEnum;
use App\Models\Monitoring\JobTracking;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Exceptions\InvalidFormatException;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class JobTrackingTable extends PowerGridComponent
{
    public string $tableName = 'job-tracking-table-mephs2-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.job_tracking_detail')
                ->showCollapseIcon()
        ];
    }

    public function datasource(): Builder
    {
        return JobTracking::select('id', 'job_id', 'job_type', 'status', 'error_message', 'result', 'attempts', 'started_at', 'completed_at', 'failed_at', 'created_at')
                        ->where('user_id', Auth::id())
                        ->orderBy('created_at', 'desc');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('job_id')
            ->add('job_type', function ($job) {
                $enum = JobTypeEnum::tryFrom($job->job_type);
                return $enum?->label() ?? __('Unknown');
            })
            ->add('status', fn ($job) => view('components.ui_widgets.status-widget', [
                'status' => $job->status,
                'text' => JobStatusEnum::from($job->status)->label()
            ]))
            ->add('attempts')
            ->add('started_at') // original for search
            ->add('started_at_local')
            ->add('status_timestamp', function ($job) {
                if ($job->status === 'completed') {
                    return $job->completed_at_local;
                } elseif ($job->status === 'failed') {
                    return $job->failed_at_local;
                }
                return '-';
            })
            ->add('completed_at') // original for search
            ->add('failed_at'); // original for search
    }

    public function columns(): array
    {
        return [

            Column::make(__('job.fields.job_type'), 'job_type')
                ->sortable(),

            Column::make(__('job.fields.status'), 'status'),

            Column::make(__('job.fields.attempts'), 'attempts'),

            Column::make(__('job.fields.started_at'), 'started_at_local')
                ->sortable(),

            Column::make(__('job.fields.completed_at'), 'status_timestamp')
                ->sortable(),

            // hidden coulumns for search
            Column::make('started_at', 'started_at')
                ->hidden(isHidden: true, isForceHidden: true)
                ->searchable(),
            Column::make('completed_at', 'completed_at')
                ->hidden(isHidden: true, isForceHidden: true),
            Column::make('failed_at', 'failed_at')
                ->hidden(isHidden: true, isForceHidden: true),

            Column::action(__('messages.global.action'))
        ];
    }

    public function beforeSearch(?string $field, ?string $search)
    {
        if (in_array($field, ['started_at', 'completed_at', 'failed_at'])) {
            try {
                return Carbon::parse($search, config('app.timezone_display', 'UTC'))
                    ->setTimezone('UTC')
                    ->format('Y-m-d H:i');
            } catch (InvalidFormatException $e) {
                return ''; 
            }
        }

        return $search;
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
                }, JobStatusEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),

            Filter::select('job_type', 'job_type')
                ->dataSource(array_map(function ($jobType) {
                    return [
                        'id' => $jobType->value,
                        'name' => $jobType->label(),
                    ];
                }, JobTypeEnum::cases()))
                ->optionValue('id')
                ->optionLabel('name'),

        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert('.$rowId.')');
    }

    public function actions(JobTracking $row): array
    {
        return [
            Button::add('retry_job')
                ->slot(' <i class="fa-solid fa-rotate"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-warning border-warning hover:bg-warning hover:text-white focus:bg-warning focus:text-white active:bg-warning active:text-white focus:ring-warning')
                ->route('admin.monitoring.job.retry', ['jobId' => $row->job_id])
                ->can($row->status === 'failed'),

            Button::add('delete_job')
                ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
                ->class('px-2 py-1 text-xs inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150
                bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->route('admin.monitoring.job.delete', ['jobId' => $row->job_id]),
        ];
    }
}
