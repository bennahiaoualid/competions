<?php

namespace App\PowerGridThemes;

use PowerComponents\LivewirePowerGrid\Themes\Components\Table;
use PowerComponents\LivewirePowerGrid\Themes\Tailwind;
use PowerComponents\LivewirePowerGrid\Themes\Theme;

class TailwindStriped extends Tailwind
{
    public function table(): array
    {
        return [
            'layout' => [
                'table' => 'min-w-full dark:!bg-primary-800',
                'div' => 'max-h-[30rem] rounded-t-lg relative border-x border-t border-pg-primary-200 dark:bg-pg-primary-700 dark:border-pg-primary-600',
                'container' => '', // Add container classes here if needed
                'base' => '', // Add base layout classes if needed
                'actions' => 'flex gap-2',
            ],

            'header' => [
                'thead' => 'sticky -top-[0.3px] relative bg-pg-primary-200 shadow-sm rounded-t-lg',
                'tr' => '',
                'th' => 'font-extra bold px-2 pr-4 py-3 text-left text-sm text-pg-primary-800 capitalize tracking-wider whitespace-nowrap dark:text-pg-primary-300',
                'thAction' => '!font-bold',
            ],

            'body' => [
                'tbody' => 'text-pg-primary-800',
                'tbodyEmpty' => '',
                'tr' => 'even:bg-neutral-100 dark:even:bg-pg-primary-700 border-b border-pg-primary-100 dark:border-pg-primary-600 hover:bg-pg-primary-50 dark:bg-pg-primary-800 dark:hover:bg-pg-primary-800',
                'td' => 'text-wrap leading-loose px-3 py-2 whitespace-nowrap dark:text-pg-primary-200',
                'tdEmpty' => 'px-3 py-2 whitespace-nowrap dark:text-pg-primary-200',
                'tdSummarize' => 'px-3 py-2 whitespace-nowrap dark:text-pg-primary-200 text-sm text-pg-primary-600 text-right space-y-2',
                'trSummarize' => '',
                'tdFilters' => '',
                'trFilters' => 'sticky top-[39px] bg-white shadow-sm dark:bg-pg-primary-800',
                'tdActionsContainer' => 'flex gap-2',
            ],
        ];
    }


}
