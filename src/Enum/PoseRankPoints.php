<?php

namespace App\Enum;

enum PoseRankPoints: int {
    case BestInShow = 5;
    case First = 4;
    case Second = 3;
    case Third = 2;
    case HonorableMention = 1;
}