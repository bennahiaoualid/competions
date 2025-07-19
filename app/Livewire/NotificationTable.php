<?php

namespace App\Livewire;


use Livewire\Attributes\On;
use App\Helpers\DateTimeHelper;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NotificationTranslator;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class NotificationTable extends PowerGridComponent
{
    public string $tableName = 'notification-table-pc5vko-table';

    public function datasource(): \Illuminate\Database\Eloquent\Builder
    {
        $notifiable = Auth::guard('admin')->user() ?? Auth::user();
        if (!$notifiable) {
            // Return an empty query builder
            return \Illuminate\Notifications\DatabaseNotification::query()->whereRaw('0=1');
        }
        return \Illuminate\Notifications\DatabaseNotification::query()
            ->where('notifiable_id', $notifiable->id)
            ->where('notifiable_type', get_class($notifiable))
            ->orderBy('created_at', 'desc');
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('bulk-delete')
                ->slot('<i class="fa-solid fa-trash me-2"></i>'. __('form.actions.delete') . ' (<span x-text="window.pgBulkActions.count(\'' . $this->tableName . '\')"></span>)')
                ->class('inline-flex items-center border rounded-md font-semibold uppercase cursor-pointer tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 px-4 py-2 text-xs bg-danger text-white border-transparent hover:bg-danger-dark focus:bg-danger-dark active:bg-danger-dark focus:ring-danger')
                ->dispatch('bulkDelete.' . $this->tableName, [])
        ];
    }

    #[On('bulkDelete.{tableName}')]
    public function bulkDelete(): void
    {
        $this->js('dispatchEvent(new CustomEvent("open-modal", {
                            detail: { detail: "delete_notifications", value: window.pgBulkActions.get(\'' . $this->tableName . '\')}
                            }))'
        );
    }

    public function fields(): PowerGridFields
    {

        return PowerGrid::fields()
            ->add('title', function ($notification) {
                return NotificationTranslator::getFieldOrTranslation($notification,'title');
            })
            ->add('message', function ($notification) {
                $msg = NotificationTranslator::getFieldOrTranslation($notification, 'message');
                // Word wrap at 60 chars, insert <br> for each line
                return nl2br(wordwrap($msg, 60, "\n", true));
            })
            ->add('created_at_formatted', function ($notification) {
                return DateTimeHelper::toLocalString($notification->created_at);
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__('notifications.title'), 'title')
                ->searchable()
                ->sortable(),
            Column::make(__('notifications.data'), 'message'),
            Column::make(__('notifications.created_at'), 'created_at_formatted')
                ->sortable(),
            Column::action(__('messages.global.action'))
        ];
    }
}
