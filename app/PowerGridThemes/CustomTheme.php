<?php

namespace App\PowerGridThemes;

use PowerComponents\LivewirePowerGrid\Themes\Tailwind;

class CustomTheme extends Tailwind
{
    public string $name = 'tailwind';

    public function table(): array
    {
        return [
            'layout' => [
                'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
                'div' => 'my-3 overflow-x-auto bg-white shadow-lg rounded-lg overflow-y-auto relative',
                'container' => '',
                'base' => '',
                'actions' => 'flex gap-2',
            ],

            'header' => [
                'thead' => 'bg-gray-50 dark:bg-gray-800',
                'tr' => '',
                'th' => 'py-3.5 px-4 text-sm font-normal text-left rtl:text-right text-gray-500 dark:text-gray-400',
                'thAction' => '!font-bold text-right',
            ],

            'body' => [
                'tbody' => 'bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-900',
                'tbodyEmpty' => '',
                'tr' => 'border border-slate-100 dark:border-slate-400 hover:bg-slate-50 dark:bg-slate-700 dark:odd:bg-slate-800 dark:odd:hover:bg-slate-900 dark:hover:bg-slate-700',
                'td' => 'px-4 py-4 text-sm font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap',
                'tdEmpty' => 'px-4 py-4 text-sm font-medium text-gray-700 dark:text-gray-200 whitespace-nowrap',
                'tdSummarize' => 'px-3 py-2 whitespace-nowrap dark:text-slate-200 text-sm text-slate-600 text-right space-y-2',
                'trSummarize' => '',
                'tdFilters' => '',
                'trFilters' => 'bg-white shadow-sm dark:bg-slate-700',
                'tdActionsContainer' => 'flex gap-2',
            ],
        ];
    }

    public function footer(): array
    {
        return [
            'view' => $this->root() . '.footer',
            'select' => 'block appearance-none bg-none border border-slate-300 text-slate-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
        ];
    }

    public function actions(): array
    {
        return [
            'headerBtn' => 'block w-full bg-slate-50 text-slate-700 border border-slate-200 rounded py-2 px-3 leading-tight focus:outline-none focus:bg-white focus:border-slate-600 dark:border-slate-500 dark:bg-slate-600 2xl:dark:placeholder-slate-300 dark:text-slate-200 dark:text-slate-300',
            'rowsBtn' => 'focus:outline-none text-sm py-2.5 px-5 rounded border',
        ];
    }

    public function cols(): array
    {
        return [
            'div' => '',
            'clearFilter' => ['', ''],
        ];
    }

    public function editable(): array
    {
        return [
            'view' => $this->root() . '.editable',
            'span' => 'flex justify-between',
            'input' => 'dark:bg-slate-700 bg-slate-50 text-black-700 border border-slate-400 rounded py-2 px-3 leading-tight focus:outline-none focus:bg-white focus:border-slate-500 dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500 p-2',
        ];
    }

    public function checkbox(): array
    {
        return [
            'th' => 'px-6 py-3 text-left text-xs font-medium text-slate-500 tracking-wider',
            'label' => 'flex items-center space-x-3',
            'input' => 'h-4 w-4',
        ];
    }

    public function filterBoolean(): array
    {
        return [
            'view' => $this->root() . '.filters.boolean',
            'base' => 'min-w-[5rem]',
            'select' => 'appearance-none block mt-1 mb-1 bg-white border border-slate-300 text-slate-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 w-full active dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
        ];
    }

    public function filterDatePicker(): array
    {
        return [
            'base' => 'p-2',
            'view' => $this->root() . '.filters.date-picker',
            'input' => 'flatpickr flatpickr-input block my-1 bg-white border border-slate-300 text-slate-700 py-2 px-3 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 w-full active dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
        ];
    }

    public function filterMultiSelect(): array
    {
        return [
            'base' => 'inline-block relative w-full p-2 min-w-[180px]',
            'view' => $this->root() . '.filters.multi-select',
        ];
    }

    public function filterNumber(): array
    {
        return [
            'view' => $this->root() . '.filters.number',
            'input' => 'block bg-white border border-slate-300 text-slate-700 py-2 px-3 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 w-full active dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500 min-w-[5rem]',
        ];
    }

    public function filterSelect(): array
    {
        return [
            'view' => $this->root() . '.filters.select',
            'base' => 'min-w-[9.5rem]',
            'select' => 'appearance-none block bg-white border border-slate-300 text-slate-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 w-full active dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
        ];
    }

    public function filterInputText(): array
    {
        return [
            'view' => $this->root() . '.filters.input-text',
            'base' => 'min-w-[9.5rem]',
            'select' => 'appearance-none block bg-white border border-slate-300 text-slate-700 py-2 px-3 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-slate-500 w-full active dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
            'input' => 'w-full block bg-white text-slate-700 border border-slate-300 rounded py-2 px-3 leading-tight focus:outline-none focus:bg-white focus:border-slate-500 dark:bg-slate-600 dark:text-slate-200 dark:placeholder-slate-200 dark:border-slate-500',
        ];
    }
}
