<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity]
class UserProfile implements JsonSerializable {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'options', targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(length: 1000)]
    private ?string $description;

    #[ORM\Column(length: 50)]
    private ?string $username;

    #[ORM\Column(name: 'display_name', length: 100)]
    private ?string $displayName;

    #[ORM\Column(length: 200)]
    private ?string $website;

    #[ORM\Column]
    private bool $private;

    #[ORM\Column(length: 60)]
    private ?string $pic;

    #[ORM\Column]
    private ?int $picHeight;

    #[ORM\Column]
    private ?int $picWidth;

    #[ORM\Column]
    private bool $hideName;

    public function __construct(
        User $user,
        ?string $description,
        ?string $username,
        ?string $displayName,
        ?string $website,
        bool $private,
        ?string $pic,
        ?int $picHeight,
        ?int $picWidth,
        bool $hideName,
    ) {
        $this->user = $user;
        $this->description = $description;
        $this->username = $username;
        $this->displayName = $displayName;
        $this->website = $website;
        $this->private = $private;
        $this->pic = $pic;
        $this->picHeight = $picHeight;
        $this->picWidth = $picWidth;
        $this->hideName = $hideName;
    }

    public function getDescription(): ?string {
        return $this->description;
    }
    public function setDescription(?string $value): self {
        $this->description = $value;
        return $this;
    }

    public function getUsername(): ?string {
        return $this->username;
    }
    public function setUsername(?string $value): self {
        $this->username = $value;
        return $this;
    }

    public function getDisplayName(): ?string {
        return $this->displayName;
    }
    public function setDisplayName(?string $value): self {
        $this->displayName = $value;
        return $this;
    }

    public function getWebsite(): ?string {
        return $this->website;
    }
    public function setWebsite(?string $value): self {
        $this->website = $value;
        return $this;
    }

    public function getPrivate(): bool {
        return $this->private;
    }
    public function setPrivate(bool $value): self {
        $this->private = $value;
        return $this;
    }

    public function getPic(): ?string {
        return $this->pic;
    }
    public function setPic(?string $value): self {
        $this->pic = $value;
        return $this;
    }

    public function getPicWidth(): ?int {
        return $this->picWidth;
    }
    public function setPicWidth(?int $value): self {
        $this->picWidth = $value;
        return $this;
    }

    public function getPicHeight(): ?int {
        return $this->picHeight;
    }
    public function setPicHeight(?int $value): self {
        $this->picHeight = $value;
        return $this;
    }

    public function getHideName(): bool {
        return $this->hideName;
    }
    public function setHideName(bool $value): self {
        $this->hideName = $value;
        return $this;
    }


    public function jsonSerialize(): array {
        return [
            'userId' => $this->user->getId(),
            'description' => $this->description ?? '',
            'username' => $this->username ?? '',
            'displayName' => $this->displayName ?? '',
            'website' => $this->website ?? '',
            'private' => $this->private,
            'pic' => $this->pic ?? '',
            'picHeight' => $this->picHeight ?? '',
            'picWidth' => $this->picWidth ?? '',
            'hideName' => $this->hideName,
        ];
    }
}