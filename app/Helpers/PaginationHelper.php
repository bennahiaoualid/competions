<?php

namespace App\Helpers;

class PaginationHelper
{
    public static function perPage(): int
    {
        return (int) request('perPage', config('pagination.default_per_page', 5));
    }
}