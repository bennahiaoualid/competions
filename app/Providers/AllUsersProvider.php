<?php
namespace App\Providers;


use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

class AllUsersProvider extends EloquentUserProvider

{
    public function __construct(Hasher $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }
    public function retrieveById($identifier)

    {

        return $this->createModel()

            ->newQuery()

            ->withoutGlobalScopes()

            ->find($identifier);

    }

    // You might also need to override retrieveByCredentials if necessary
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return;
        }

        // Remove 'password' from the credentials array
        $query = $this->createModel()->newQuery()->withoutGlobalScopes();

        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

}
