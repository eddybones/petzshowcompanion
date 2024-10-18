<?php

namespace App\Tests\Service;

use App\Enum\ShowType;
use App\Service\ShowTitleService;
use PHPUnit\Framework\TestCase;

class ShowTitleServiceUnitTest extends TestCase {
    public function testPoseTitles(): void {
        $title = fn(int $points): string => ShowTitleService::getTitle(ShowType::Pose, $points);

        $this->assertEquals('', $title(0));
        $this->assertEquals('', $title(4));

        $this->assertEquals('Reserve Champion', $title(5));
        $this->assertEquals('Reserve Champion', $title(9));

        $this->assertEquals('Champion', $title(10));
        $this->assertEquals('Champion', $title(14));

        $this->assertEquals('Grand Champion', $title(15));
        $this->assertEquals('Grand Champion', $title(19));

        $this->assertEquals('Master Grand Champion', $title(20));
        $this->assertEquals('Master Grand Champion', $title(29));

        $this->assertEquals('Supreme Grand Champion', $title(30));
        $this->assertEquals('Supreme Grand Champion', $title(49));

        $this->assertEquals('Ultimate Grand Champion', $title(50));
        $this->assertEquals('Ultimate Grand Champion', $title(89));

        $this->assertEquals('Reserve World Champion', $title(90));
        $this->assertEquals('Reserve World Champion', $title(99));

        $this->assertEquals('World Champion', $title(100));
        $this->assertEquals('World Champion', $title(199));

        $this->assertEquals('Reserve Legendary Champion', $title(200));
        $this->assertEquals('Reserve Legendary Champion', $title(299));

        $this->assertEquals('Legendary Champion', $title(300));
        $this->assertEquals('Legendary Champion', $title(499));

        $this->assertEquals('Legend', $title(500));
    }

    public function testFrisbeeTitles(): void {
        $title = fn(int $points): string => ShowTitleService::getTitle(ShowType::Frisbee, $points);

        $this->assertEquals('', $title(0));
        $this->assertEquals('', $title(9));

        $this->assertEquals('Frisbee Dog', $title(10));
        $this->assertEquals('Frisbee Dog', $title(19));

        $this->assertEquals('Advanced Frisbee Dog', $title(20));
        $this->assertEquals('Advanced Frisbee Dog', $title(29));

        $this->assertEquals('Frisbee Dog of Excellence', $title(30));
    }
}