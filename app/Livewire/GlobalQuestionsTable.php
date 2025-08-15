<?php

namespace App\Livewire;

use App\Models\GuestUsers\GlobalQuestion;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;


final class GlobalQuestionsTable extends PowerGridComponent
{
    public string $tableName = 'GlobalQuestionsTable';
    
    public function setUp(): array
    {

        return [

            PowerGrid::header()
                ->showToggleColumns()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->view('components.tables.question_choices')
                ->showCollapseIcon()
        ];
    }

    public function datasource(): Builder
    {
        if (Auth::user()->hasRole(['super_admin','owner'])){
            return GlobalQuestion::query()->with(['createdBy','choices','approvedBy']);
        }else{
            return GlobalQuestion::query()->whereHas('createdBy', function ($query) {
                $query->where('id', Auth::id());
            })->with(['createdBy','approvedBy','choices']);
        }
    }

    public function relationSearch(): array
    {
        return [
            'createdBy' => [ // relationship on dishes model
                'name', // column enabled to search
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('question_text')
            ->add('score')
            ->add('duration')
            ->add('admin_id')
            ->add('deleted_admin_name')
            ->add('created_by', function ($question) {
                $admin = $question->createdBy;
                if ($admin){
                    return sprintf(
                        '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                        route("admin.edit",["id" => e($admin->id)]),
                        e($admin->name)
                    );
                }else{
                    return e($question->deleted_admin_name);
                }
            })
            ->add('approved_by', function ($question) {
                $admin = $question->approvedBy;
                if ($admin){
                    return sprintf(
                        '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                        route("admin.edit",["id" => e($admin->id)]),
                        e($admin->name)
                    );
                }else{
                    return Blade::render(
                        '<x-status-widget status="inactive" text="'.__('messages.global.not_approved'). '" />'
                    );
                }

            });

    }

    public function columns(): array
    {
        return [

            Column::make(__('competition.question.question_text'), 'question_text')
                ->searchable(),

            Column::make(__('competition.response.score'), 'score')
                ->sortable(),

            Column::make(__('competition.question.duration'), 'duration'),

            Column::make(__("messages.global.created_by"), 'created_by')
                ->searchable(),
            Column::make(__("messages.global.approved_by"), 'approved_by'),



            Column::action('Action')
        ];
    }



    public function filters(): array
    {
        return [
        ];
    }


    public function actions(GlobalQuestion $row): array
    {
        return [
            Button::add('approve')
                ->slot('<i class="fa-solid fa-check text-lg"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-0.5 text-xs bg-transparent text-success border-success hover:bg-success hover:text-white focus:bg-success focus:text-white active:bg-success active:text-white focus:ring-success')
                ->dispatch('open-modal', ['detail' => 'approve', 'value' => $row->id])
                ->can(
                    $row->canDelete() && $row->approved == null
                ),
            Button::add('delete_competitions')
                ->slot(' <i class="fa-solid fa-unlock text-base"></i>')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-2 py-0.5 text-xs
            bg-transparent text-danger border-danger hover:bg-danger hover:text-white focus:bg-danger focus:text-white active:bg-danger active:text-white focus:ring-danger')
                ->can($row->canDelete())
                ->dispatch('open-modal', ['detail' => 'delete', 'value' => $row->id]),

        ];
    }

    public function customThemeClass(): ?string
    {
        return TailwindStriped::class;
    }
}
