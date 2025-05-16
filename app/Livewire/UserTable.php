<?php

namespace App\Livewire;

use App\Enums\Gender;
use App\Models\User;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid; 
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class UserTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'UserTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::responsive()
        ];
    }

    public function datasource(): Builder
    {
        return User::query()->with("admin");
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name')
            ->add('email')
            ->add('birthdate')
            ->add('gender', function ($user) {
                return e(__("user.profile.genders.".$user->gender));
            })
            ->add('active', function ($user) {
                $verified = $user->email_verified_at == null ? 'inactive': 'active';
                return Blade::render(
                    '<x-status-widget status="'. $verified .
                    '" text="'.__('user.profile.status.'. $verified ). '" />'
                );
            })
            ->add('created_by', function ($user) {
                $admin = $user->admin;
                if ($admin){
                    return sprintf(
                        '<a target="_blank"
                    class="underline text-blue-600 hover:text-blue-800"
                    href="%s">%s</a>',
                        route("admin.edit",["id" => e($admin->id)]),
                        e($user->admin->name)
                    );
                }else{
                    return e($user->admin_id);
                }

            });


    }

    public function columns(): array
    {
        return [
            Column::make(__("user.profile.name"), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__("user.profile.email"), 'email')
                ->searchable(),
            Column::make(__("user.profile.birthdate"), 'birthdate')
                ->searchable(),
            Column::make(__("user.profile.genders.gender"), 'gender'),
            Column::make(__('user.profile.status.state'), 'active'),
            Column::make(__("user.profile.actions.created_by"), 'created_by'),
            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::enumSelect('gender','gender')
                ->datasource(Gender::cases())
                ->optionLabel('gender'),

        ];
    }

    public function actions(User $row): array
    {
        return [
            Button::add('my-custom-button')
                ->slot(view('components.tables.user-table-action-buttons', ['row' => $row])->render())
        ];
    }

    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
