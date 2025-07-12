<?php
namespace App\Exceptions\Contracts;

interface UserFriendlyException
{
    public function translationKey(): string;
    public function contextData(): array;
}
