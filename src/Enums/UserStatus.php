<?php

namespace Bot\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Активный',
            self::BLOCKED => 'Заблокирован',
            self::INACTIVE => 'Неактивный',
            self::BANNED => 'Забанен',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::BLOCKED => 'red',
            self::INACTIVE => 'gray',
            self::BANNED => 'dark-red',
        };
    }
} 