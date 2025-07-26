<?php

namespace App\Helpers;

class PaginationHelper
{
    public static function perPage($set_default = null): int
    {
        return (int) request('perPage', $set_default ?? config('pagination.default_per_page', 5));
    }
}