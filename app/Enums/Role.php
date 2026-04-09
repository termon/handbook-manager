<?php

namespace App\Enums;

use App\Traits\EnumOptions;

enum Role: string
{
    use EnumOptions;

    case ADMIN = "admin";
    case USER  = "user";
    case GUEST = "guest";
    case AUTHOR = "author";
}
