<?php

namespace App\Livewire;

use App\Enums\Gender;
use App\Models\Admin\Admin;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AdminTable extends PowerGridComponent
{
    public string $tableName = 'AdminTable';
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
    public function header(): array
    {
        return [

        ];
    }

    public function datasource(): Builder
    {
        if (Auth::user()->hasRole('owner')){
            return Admin::query()
                ->select('id', 'name', 'email', 'birthdate', 'gender')
                ->where('id', '!=', Auth::id());
        }
        else{
            return Admin::withoutRoles(['owner', 'super_admin'])
                ->select('id', 'name', 'email', 'birthdate', 'gender')
                ->where('id', '!=', Auth::id());
        }
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
            ->add('gender', function ($admin) {
                return e(__("user.profile.genders.".$admin->gender));
            });
    }

    public function columns(): array
    {
        return [
            Column::make(__("user.profile.name"), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__("user.profile.email"), 'email'),
            Column::make(__("user.profile.birthdate"), 'birthdate')
                ->searchable(),
            Column::make(__("user.profile.genders.gender"), 'gender'),
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

    public function actions(Admin $row): array
    {
        return [
            Button::add('my-custom-button')
                ->can( Auth::user()->hasRole(['super_admin','owner'], 'admin'))
                ->slot(view('components.tables.action-buttons', ['row' => $row])->render())
        
            ];
    }

    public function customThemeClass(): ?string
    {
        return TailwindStriped::class;
    }
}
