<?php

namespace App\Enums;

enum Gender : string
{
    //case ALL      = "";
    case Male    = "male";
    case Female   = "female";

    public function labels(): string
    {
        return match ($this) {
           // self::ALL         => "All",
            self::Male       => __("user.profile.genders.male"),
            self::Female      => __("user.profile.genders.female"),
        };
    }

    public function labelPowergridFilter(): string
    {
        return $this->labels();
    }
}
