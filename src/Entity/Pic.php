<?php

namespace App\Entity;

use App\Repository\PicRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PicRepository::class)]
#[ORM\Table(name: 'pet_pics')]
class Pic {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    /**
     * This is the md5 hash (32 chars) plus the extension (.xxx)
     */
    #[ORM\Column(length: 36, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(name: '`order`')]
    private int $order;

    public function __construct(
        Pet    $pet,
        string $file,
        int    $order,
    ) {
        $this->pet = $pet;
        $this->file = $file;
        $this->order = $order;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getFile(): string {
        return $this->file;
    }

    public function getOrder(): int {
        return $this->order;
    }
    public function setOrder(int $order): self {
        $this->order = $order;
        return $this;
    }
}