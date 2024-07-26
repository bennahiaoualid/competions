<?php

namespace App\PowerGridThemes;

use PowerComponents\LivewirePowerGrid\Themes\Components\Table;
use PowerComponents\LivewirePowerGrid\Themes\Tailwind;
use PowerComponents\LivewirePowerGrid\Themes\Theme;

class TailwindStriped extends Tailwind
{
    public function table(): Table
    {
        return Theme::table('min-w-full dark:!bg-primary-800')
            ->div('max-h-[30rem] rounded-t-lg relative border-x border-t border-pg-primary-200 dark:bg-pg-primary-700 dark:border-pg-primary-600')
            ->thead( 'sticky -top-[0.3px] relative bg-pg-primary-200 shadow-sm rounded-t-lg ')
            ->thAction('!font-bold')
            ->tdAction('')
            ->tr('')
            ->trFilters('sticky top-[39px] bg-white shadow-sm dark:bg-pg-primary-800')
            ->th('font-extra bold px-2 pr-4 py-3 text-left text-sm text-pg-primary-800 capitalize tracking-wider whitespace-nowrap dark:text-pg-primary-300')
            ->tbody('text-pg-primary-800')
            ->trBody('even:bg-neutral-100 dark:even:bg-pg-primary-700 border-b border-pg-primary-100 dark:border-pg-primary-600 hover:bg-pg-primary-50 dark:bg-pg-primary-800 dark:hover:bg-pg-primary-800')
            ->tdBody('px-3 py-2 whitespace-nowrap dark:text-pg-primary-200')
            ->tdBodyEmpty('px-3 py-2 whitespace-nowrap dark:text-pg-primary-200')
            ->trBodyClassTotalColumns('')
            ->tdBodyTotalColumns('px-3 py-2 whitespace-nowrap dark:text-pg-primary-200 text-sm text-pg-primary-600 text-right space-y-2');
    }

}
