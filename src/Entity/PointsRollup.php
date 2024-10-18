<?php

namespace App\Entity;

use App\Enum\ShowType;
use App\Repository\PointsRollupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointsRollupRepository::class)]
class PointsRollup {
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
    private int $total = 0;

    public function __construct(Pet $pet, ShowType $showType) {
        $this->pet = $pet;
        $this->showType = $showType;
    }

    public function getShowType(): ShowType {
        return $this->showType;
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function incrementTotal(int $points): self {
        $this->total += $points;
        return $this;
    }
}