<?php

namespace App\Entity;

use App\Enum\ShowType;
use App\Repository\PointsRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointsRepository::class)]
class Points {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[ORM\Column(nullable: false)]
    private ShowType $showType;

    #[ORM\Column(nullable: false)]
    private int $points;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $addedOn = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $modifiedOn;

    public function __construct(
        Pet                $pet,
        ShowType           $showType,
        int                $points,
        ?DateTimeInterface $modifiedOn = null
    ) {
        $this->pet = $pet;
        $this->showType = $showType;
        $this->points = $points;
        $this->modifiedOn = $modifiedOn;
        if($this->addedOn === null) {
            $this->addedOn = new DateTime();
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getShowType(): ShowType {
        return $this->showType;
    }

    public function getPoints(): int {
        return $this->points;
    }

    public function getAddedOn(): DateTime {
        return $this->addedOn;
    }
}