<?php

namespace App\Livewire;

use App\Enums\Gender;
use App\Models\Admin\Admin;
use App\PowerGridThemes\TailwindStriped;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Cache;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\Lazy;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class AdminTable extends PowerGridComponent
{
    use WithExport;
    public bool $deferLoading = true;

    public function setUp(): array
    {
        return [
            Cache::make()
                ->forever()
                ->prefix(Auth::id() . '_'),
            Header::make()
                ->showSearchInput(),
            Footer::make()
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
            return Admin::query()->where('id', '!=', Auth::id());
        }
        else{
            return Admin::withoutRoles(['owner', 'super_admin'])->where('id', '!=', Auth::id());
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
                ->can(allowed: Auth::user()->hasRole(['super_admin','owner'], 'admin'))
                ->bladeComponent('tables.action-buttons', ['row' => $row])
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
    public function template(): ?string
    {
        return TailwindStriped::class;
    }
}
