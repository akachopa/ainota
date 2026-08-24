<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Disabled = 'disabled';
}
