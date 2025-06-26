<?php

namespace App\Livewire;

use App\Enums\JobTypeEnum;
use App\Enums\JobStatusEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use App\Models\Monitoring\JobTracking;
use Auth;
use Illuminate\Database\Eloquent\Builder;
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
            ->add('started_at_local')
            ->add('status_timestamp', function ($job) {
                if ($job->status === 'completed') {
                    return $job->completed_at_local;
                } elseif ($job->status === 'failed') {
                    return $job->failed_at_local;
                }
                return '-';
            });
    }

    public function columns(): array
    {
        return [

            Column::make(__('job.fields.job_type'), 'job_type')
                ->sortable()
                ->searchable(),

            Column::make(__('job.fields.status'), 'status')
                ->sortable()
                ->searchable(),

            Column::make(__('job.fields.attempts'), 'attempts')
                ->sortable()
                ->searchable(),

            Column::make(__('job.fields.started_at'), 'started_at_local')
                ->sortable()
                ->searchable(),

            Column::make(__('job.fields.completed_at'), 'status_timestamp')
                ->sortable()
                ->searchable(),

            Column::action(__('messages.global.action'))
        ];
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
            Button::add('edit')
                ->slot('Edit: ')
                ->id()
                ->class('pg-btn-white dark:ring-pg-primary-600 dark:border-pg-primary-600 dark:hover:bg-pg-primary-700 dark:ring-offset-pg-primary-800 dark:text-pg-primary-300 dark:bg-pg-primary-700')
                ->route('admin.monitoring.job.retry', ['jobId' => $row->job_id])
            ];
    }
}
