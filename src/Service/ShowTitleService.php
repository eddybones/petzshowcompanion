<?php

namespace App\Service;

use App\Enum\PetType;
use App\Enum\ShowType;

final class ShowTitleService {
    public static function getTitle(ShowType $type, PetType $petType, int $points): string {
        switch($type) {
            case ShowType::Pose:
                return self::pose($points);
            case ShowType::Trick:
                return self::trick($points, $petType);
            case ShowType::Agility:
                return self::agility($points, $petType);
            case ShowType::Frisbee:
                return self::frisbee($points);
            case ShowType::Mousing:
                return self::mousing($points, $petType);
        }
    }

    private static function pose(int $points): string {
        if($points >= 5 && $points <= 9) {
            return 'Reserve Champion';
        }
        if($points >= 10 && $points <= 14) {
            return 'Champion';
        }
        if($points >= 15 && $points <= 19) {
            return 'Grand Champion';
        }
        if($points >= 20 && $points <= 29) {
            return 'Master Grand Champion';
        }
        if($points >= 30 && $points <= 49) {
            return 'Supreme Grand Champion';
        }
        if($points >= 50 && $points <= 89) {
            return 'Ultimate Grand Champion';
        }
        if($points >= 90 && $points <= 99) {
            return 'Reserve World Champion';
        }
        if($points >= 100 && $points <= 199) {
            return 'World Champion';
        }
        if($points >= 200 && $points <= 299) {
            return 'Reserve Legendary Champion';
        }
        if($points >= 300 && $points <= 499) {
            return 'Legendary Champion';
        }
        if($points >= 500) {
            return 'Legend';
        }
        return '';
    }

    private static function trick(int $points, PetType $petType): string {
        if($points >= 10 && $points <= 19) {
            return 'Trick ' . $petType->name;
        }
        if($points >= 20 && $points <= 29) {
            return 'Trick ' . $petType->name . ' Advanced';
        }
        if($points >= 30) {
            return 'Trick ' . $petType->name . ' of Excellence';
        }
        return '';
    }

    private static function agility(int $points, PetType $petType): string {
        if($points >= 10 && $points <= 14) {
            return 'Agility ' . $petType->name;
        }
        if($points >= 15 && $points <= 29) {
            return 'Agility ' . $petType->name . ' Excellence';
        }
        if($points >= 30) {
            return 'Agility ' . $petType->name . ' Champion';
        }
        return '';
    }

    private static function frisbee(int $points): string {
        if($points >= 10 && $points <= 19) {
            return 'Frisbee Dog';
        }
        if($points >= 20 && $points <= 29) {
            return 'Advanced Frisbee Dog';
        }
        if($points >= 30) {
            return 'Frisbee Dog of Excellence';
        }
        return '';
    }

    private static function mousing(int $points, PetType $petType): string {
        if($points >= 10 && $points <= 19) {
            return 'Mousing ' . $petType->name;
        }
        if($points >= 20 && $points <= 29) {
            return 'Distinguished Mouser';
        }
        if($points >= 30) {
            return 'Distinguished Mouser';
        }
        return '';
    }
}