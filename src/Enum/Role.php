<?php

namespace App\Enum;

enum Role: string {
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
}