<?php

namespace Tests;

use Mcamara\LaravelLocalization\LaravelLocalization;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplicationWithLocale($locale)
    {
        self::tearDown();
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);
        self::setUp();
    }
}
