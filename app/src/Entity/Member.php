<?php

namespace App\Entity;

use App\Repository\MemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
class Member implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTime $birthDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $baptDate = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $active = null;

    #[ORM\Column]
    private ?DateTime $lastUpdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $apiToken = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $apiTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBaptDate(): ?DateTime
    {
        return $this->baptDate;
    }

    public function setBaptDate(?DateTime $baptDate): static
    {
        $this->baptDate = $baptDate;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getLastUpdate(): ?DateTime
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(DateTime $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $token): self
    {
        $this->apiToken = $token;

        return $this;
    }

    public function getApiTokenExpiresAt(): ?DateTime
    {
        return $this->apiTokenExpiresAt;
    }

    public function setApiTokenExpiresAt(?DateTime $date): self
    {
        $this->apiTokenExpiresAt = $date;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'birth_date' => $this->getBirthDate()->format('Y-m-d'),
            'bapt_date' => $this->getBaptDate()?->format('Y-m-d'),
            'active' => $this->getActive(),
            'email' => $this->getEmail(),
            'last_update' => $this->getLastUpdate()->format('Y-m-d H:i:s'),
        ];
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
