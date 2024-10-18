<?php

namespace App\Entity;

use App\Enum\Role;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /**
     * @var Role[]
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Pet::class, cascade: ['remove'], fetch: 'LAZY')]
    private Collection $petz;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserOptions::class, cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private ?UserOptions $options;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(name: 'dateAdded', type: 'datetime', nullable: false)]
    private DateTime $dateAdded;

    #[ORM\Column(name: 'resetToken', type: 'string', nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'resetInitiated', type: 'datetime', nullable: true)]
    private ?DateTime $resetInitiated = null;

    #[ORM\Column(length: 8)]
    private string $hash;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserProfile::class, cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private ?UserProfile $profile;

    #[ORM\Column(name: 'lastLogin', type: 'datetime', nullable: true)]
    private ?DateTime $lastLogin;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Privacy::class, cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private ?Privacy $privacy;

    #[ORM\Column(name: 'require_verification', type: 'boolean', nullable: false)]
    private bool $requireVerification;

    /**
     * @param string           $email
     * @param array            $roles
     * @param UserOptions|null $options
     * @param string           $hash
     * @param UserProfile|null $profile
     * @param Privacy|null     $privacy
     * @param bool             $requireVerification
     */
    public function __construct(string $email, array $roles = [], ?UserOptions $options = null, string $hash = '', ?UserProfile $profile = null, ?Privacy $privacy = null, bool $requireVerification = true) {
        $this->email = $email;
        $this->roles = $roles;
        $this->options = $options;
        $this->hash = $hash;
        $this->profile = $profile;
        $this->privacy = $privacy;
        $this->petz = new ArrayCollection();
        $this->dateAdded = new DateTime();
        $this->requireVerification = $requireVerification;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getEmail(): string {
        return $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string {
        return $this->email;
    }

    /**
     * @return Role[]
     * @see UserInterface
     */
    public function getRoles(): array {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        if($this->verified || !$this->requireVerification) {
            $roles[] = 'ROLE_EMAIL_VERIFIED';
        }

        return array_unique($roles);
    }
    public function setRoles(array $roles): self {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): self {
        $this->password = $password;

        return $this;
    }
    public function getOptions(): UserOptions {
        return $this->options;
    }

    public function isVerified(): bool {
        return $this->verified;
    }
    public function setVerified(bool $verified): self {
        $this->verified = $verified;

        return $this;
    }

    public function getVerificationToken(): ?string {
        return $this->verificationToken;
    }
    public function setVerificationToken(?string $verificationToken): self {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getResetToken(): ?string {
        return $this->resetToken;
    }
    public function setResetToken(?string $token): self {
        $this->resetToken = $token;

        return $this;
    }

    public function getResetInitiated(): ?DateTime {
        return $this->resetInitiated;
    }
    public function setResetInitiated(?DateTime $time): self {
        $this->resetInitiated = $time;

        return $this;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function setHash(string $hash): self {
        $this->hash = $hash;
        return $this;
    }

    public function getProfile(): ?UserProfile {
        return $this->profile;
    }

    public function getPetz(): Collection {
        return $this->petz;
    }

    public function getDateAdded(): DateTime {
        return $this->dateAdded;
    }

    public function getLastLogin(): ?DateTime {
        return $this->lastLogin;
    }
    public function setLastLogin(DateTime $value): self {
        $this->lastLogin = $value;
        return $this;
    }

    public function getPrivacy(): Privacy {
        return $this->privacy ?? $this->defaultPrivacy();
    }

    public function defaultPrivacy(): Privacy {
        return new Privacy(
            callName: false,
            showName: false,
            notes: true,
            type: false,
            retired: false,
            sex: false,
            prefix: false,
            hexerOrBreeder: false,
            birthday: false,
            user: $this,
        );
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
