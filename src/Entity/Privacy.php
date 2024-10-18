<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Privacy {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(inversedBy: 'options', targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(name: 'call_name')]
    private bool $callName;

    #[ORM\Column(name: 'show_name')]
    private bool $showName;

    #[ORM\Column(name: 'notes')]
    private bool $notes;

    #[ORM\Column(name: 'type')]
    private bool $type;

    #[ORM\Column(name: 'retired')]
    private bool $retired;

    #[ORM\Column(name: 'sex')]
    private bool $sex;

    #[ORM\Column(name: 'prefix')]
    private bool $prefix;

    #[ORM\Column(name: 'hexer_or_breeder')]
    private bool $hexerOrBreeder;

    #[ORM\Column(name: 'birthday')]
    private bool $birthday;

    public function __construct(
        bool $callName,
        bool $showName,
        bool $notes,
        bool $type,
        bool $retired,
        bool $sex,
        bool $prefix,
        bool $hexerOrBreeder,
        bool $birthday,
        User $user,
    ) {
        $this->callName = $callName;
        $this->showName = $showName;
        $this->notes = $notes;
        $this->type = $type;
        $this->retired = $retired;
        $this->sex = $sex;
        $this->prefix = $prefix;
        $this->hexerOrBreeder = $hexerOrBreeder;
        $this->birthday = $birthday;
        $this->user = $user;
    }

    public function callNameIsPrivate(): bool {
        return $this->callName;
    }
    public function makeCallNamePrivate(bool $value): self {
        $this->callName = $value;
        return $this;
    }

    public function showNameIsPrivate(): bool {
        return $this->showName;
    }
    public function makeShowNamePrivate(bool $value): self {
        $this->showName = $value;
        return $this;
    }

    public function notesIsPrivate(): bool {
        return $this->notes;
    }
    public function makeNotesPrivate(bool $value): self {
        $this->notes = $value;
        return $this;
    }

    public function typeIsPrivate(): bool {
        return $this->type;
    }
    public function makeTypePrivate(bool $value): self {
        $this->type = $value;
        return $this;
    }

    public function retiredIsPrivate(): bool {
        return $this->retired;
    }
    public function makeRetiredPrivate(bool $value): self {
        $this->retired = $value;
        return $this;
    }

    public function sexIsPrivate(): bool {
        return $this->sex;
    }
    public function makeSexPrivate(bool $value): self {
        $this->sex = $value;
        return $this;
    }

    public function prefixIsPrivate(): bool {
        return $this->prefix;
    }
    public function makePrefixPrivate(bool $value): self {
        $this->prefix = $value;
        return $this;
    }

    public function hexerOrBreederIsPrivate(): bool {
        return $this->hexerOrBreeder;
    }
    public function makeHexerOrBreederPrivate(bool $value): self {
        $this->hexerOrBreeder = $value;
        return $this;
    }

    public function birthdayIsPrivate(): bool {
        return $this->birthday;
    }
    public function makeBirthdayPrivate(bool $value): self {
        $this->birthday = $value;
        return $this;
    }
}