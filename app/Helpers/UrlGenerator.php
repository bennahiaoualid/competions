<?php

namespace App\Helpers;

class UrlGenerator
{
    /**
     * Generate a URL with the correct domain
     *
     * @param string $routeName The route name
     * @param array|mixed $parameters Route parameters
     * @return string The generated URL with correct domain
     */
    public static function url($routeName, $parameters = [])
    {
        // Use route() helper with absolute URL and ensure correct domain
        $url = route($routeName, $parameters, true);
        
        // If the URL doesn't have the correct domain, force it
        $appUrl = config('app.url');
        if ($appUrl && !str_starts_with($url, $appUrl)) {
            $path = parse_url($url, PHP_URL_PATH);
            $url = rtrim($appUrl, '/') . $path;
        }
        
        return $url;
    }
} 