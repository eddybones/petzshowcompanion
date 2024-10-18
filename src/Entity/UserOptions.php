<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class UserOptions {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'options', targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column]
    private bool $compactView = false;

    public function __construct(int $id, User $user, bool $compactView) {
        $this->id = $id;
        $this->user = $user;
        $this->compactView = $compactView;
    }

    public function useCompactView(): bool {
        return $this->compactView;
    }
    public function setCompactView(bool $use): self {
        $this->compactView = $use;
        return $this;
    }
}