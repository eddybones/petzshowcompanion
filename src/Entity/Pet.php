<?php

namespace App\Entity;

use App\Enum\PetType;
use App\Enum\Sex;
use App\Enum\ShowType;
use App\Repository\PetRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: PetRepository::class)]
class Pet implements JsonSerializable {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $callName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $showName;

    #[ORM\OneToMany(mappedBy: 'pet', targetEntity: Pic::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private Collection $pics;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'petz')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private UserInterface $user;

    #[ORM\Column(type: 'text')]
    private ?string $notes;

    #[ORM\Column(nullable: false)]
    private PetType $type;

    #[ORM\Column]
    private bool $retired;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?int $sex = null;

    #[ORM\Column(length: 8)]
    private string $hash;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $addedOn = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $modifiedOn = null;

    #[ORM\OneToMany(mappedBy: 'pet', targetEntity: Points::class, cascade: ['remove'], fetch: 'EAGER')]
    private Collection $points;

    #[ORM\OneToMany(mappedBy: 'pet', targetEntity: PointsRollup::class, cascade: ['remove'], fetch: 'EAGER')]
    /* #[ORM\OrderBy(['total' => 'desc'])]
     * Order by does not work with fetch EAGER
     * Ordering is happening in the accessor function
     */
    private Collection $pointsRollup;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $prefix = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hexerOrBreeder = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $birthday = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'pet', fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'pet_tags')]
    private Collection $tags;

    #[ORM\Column]
    private bool $private;

    public function __construct(
        UserInterface     $user,
        string            $showName = '',
        string            $callName = '',
        ?string           $notes = null,
        PetType           $type = PetType::Dog,
        bool              $retired = false,
        ?bool             $sex = null,
        string            $hash = '',
        ?string           $prefix = null,
        ?string           $hexerOrBreeder = null,
        DateTimeInterface $birthday = null,
        ArrayCollection   $tags = new ArrayCollection(),
        DateTimeInterface $modifiedOn = null,
        ?bool             $private = false,
    ) {
        $this->user = $user;
        $this->showName = $showName;
        $this->callName = $callName;
        $this->notes = $notes;
        $this->type = $type;
        $this->retired = $retired;
        $this->sex = $sex;
        $this->hash = $hash;
        $this->prefix = $prefix;
        $this->hexerOrBreeder = $hexerOrBreeder;
        $this->birthday = $birthday;
        $this->tags = $tags;
        $this->modifiedOn = $modifiedOn;
        $this->private = $private;

        if($this->addedOn === null) {
            $this->addedOn = new DateTime();
        }
        $this->points = new ArrayCollection();
        $this->pointsRollup = new ArrayCollection();
        $this->pics = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCallName(): string {
        return $this->callName;
    }

    public function setCallName(string $callName): self {
        $this->callName = $callName;
        return $this;
    }

    public function getShowName(): string {
        return $this->showName;
    }

    public function setShowName(string $showName): self {
        $this->showName = $showName;
        return $this;
    }

    public function getUser(): UserInterface {
        return $this->user;
    }

    public function getPics(): ?Collection {
        $iterator = $this->pics->getIterator();
        $iterator->uasort(fn($a, $b) => $a->getOrder() <=> $b->getOrder());
        return new ArrayCollection(iterator_to_array($iterator));
    }

    public function setPics(Collection $pics): self {
        $this->pics = $pics;
        return $this;
    }

    public function getNotes(): ?string {
        return $this->notes;
    }

    public function setNotes(string $notes): self {
        $this->notes = $notes;
        return $this;
    }

    public function getType(): PetType {
        return $this->type;
    }

    public function setType(PetType $type): self {
        $this->type = $type;
        return $this;
    }

    public function getRetired(): bool {
        return $this->retired;
    }

    public function setRetired(bool $retired): self {
        $this->retired = $retired;
        return $this;
    }

    public function getSex(): ?int {
        return $this->sex;
    }

    public function getSexName(): string {
        if($this->sex === null) {
            return '';
        }
        return Sex::from($this->sex)->name;
    }

    public function setSex(?int $sex): self {
        $this->sex = $sex;
        return $this;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function setHash(string $hash): self {
        $this->hash = $hash;
        return $this;
    }

    public function getAddedOn(): DateTimeInterface {
        return $this->addedOn;
    }

    public function getModifiedOn(): DateTimeInterface {
        return $this->modifiedOn;
    }

    public function setModifiedOn(DateTime $modifiedOn): self {
        $this->modifiedOn = $modifiedOn;
        return $this;
    }

    public function getPoints(): ?Collection {
        return $this->points;
    }

    public function getPointsRollup(): array {
        $items = $this->pointsRollup->toArray();
        // Sort highest to lowest point totals
        usort($items, fn($a, $b) => $b->getTotal() <=> $a->getTotal());
        return $items;
    }

    public function getPrefix(): ?string {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): self {
        $this->prefix = $prefix;
        return $this;
    }

    public function getHexerOrBreeder(): ?string {
        return $this->hexerOrBreeder;
    }

    public function setHexerOrBreeder(?string $hexerOrBreeder): self {
        $this->hexerOrBreeder = $hexerOrBreeder;
        return $this;
    }

    public function getBirthday(): ?DateTimeInterface {
        return $this->birthday;
    }

    public function setBirthday(?DateTimeInterface $birthday): self {
        $this->birthday = $birthday;
        return $this;
    }

    public function getTags(): Collection {
        return $this->tags;
    }

    public function setTags(Collection $tags): self {
        $this->tags = $tags;
        return $this;
    }

    public function getPrivate(): bool {
        return $this->private;
    }
    public function setPrivate(bool $value): self {
        $this->private = $value;
        return $this;
    }

    public function jsonSerialize(): array {
        $tags = [];
        foreach($this->tags as $tag) {
            $tags[] = [
                'name' => $tag->getName(),
                'private' => $tag->getPrivate(),
            ];
        }
        $pics = [];
        foreach($this->pics as $pic) {
            $pics[] = [
                'file' => $pic->getFile(),
                'order' => $pic->getOrder(),
            ];
        }
        $points = [];
        foreach($this->getPointsRollup() as $rollup) {
            $points[] = [
                'showType' => $rollup->getShowType()->name,
                'total' => $rollup->getTotal(),
            ];
        }
        return [
            'callName' => $this->callName,
            'showName' => $this->showName,
            'notes' => $this->notes,
            'type' => $this->type,
            'retired' => $this->retired,
            'sex' => $this->sex,
            'hash' => $this->hash,
            'added' => $this->addedOn,
            'prefix' => $this->prefix,
            'hexerOrBreeder' => $this->hexerOrBreeder,
            'birthday' => $this->birthday,
            'private' => $this->private,
            'pics' => $pics,
            'pointsRollup' => $points,
            'tags' => $tags,
        ];
    }
}
