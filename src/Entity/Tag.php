<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use JsonSerializable;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag implements JsonSerializable {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private UserInterface $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(length: 8)]
    private string $hash;

    #[ORM\Column]
    private bool $private;

    public function __construct(UserInterface $user, string $name, string $hash, bool $private) {
        $this->user = $user;
        $this->name = $name;
        $this->hash = $hash;
        $this->private = $private;
    }

    public function getUser(): UserInterface {
        return $this->user;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function getPrivate(): bool {
        return $this->private;
    }
    public function setPrivate(bool $value): self {
        $this->private = $value;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function jsonSerialize(): array {
        return [
            'hash' => $this->hash,
            'name' => $this->name,
            'private' => $this->private,
        ];
    }
}