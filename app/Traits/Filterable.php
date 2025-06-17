<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Filterable
{
    /**
     * Apply filters to the query
     * 
     * @param Builder|Relation $query
     * @param array $filters
     * @return Builder|Relation
     */
    protected function applyFilters($query, array $filters): Builder|Relation
    {
        return $query
            ->when(!empty($filters['title']), fn($q) => $q->title($filters['title']))
            ->when(isset($filters['start_date_from']) || isset($filters['start_date_to']),
                fn($q) => $q->startDate($filters['start_date_from'], $filters['start_date_to']))
            ->when(isset($filters['age_start']) || isset($filters['age_end']),
                fn($q) => $q->ageRange($filters['age_start'], $filters['age_end']))
            ->when(isset($filters['status']),
                fn($q) => $q->status($filters['status']));
    }
} 