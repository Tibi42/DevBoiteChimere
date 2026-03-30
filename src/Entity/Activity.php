<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un événement / activité de l'association.
 *
 * Une activité a un statut :
 *   - STATUS_PUBLISHED : visible sur le calendrier public
 *   - STATUS_PENDING   : proposition en attente de validation admin
 *
 * Les champs createdAt et updatedAt sont automatiquement gérés via les
 * lifecycle callbacks PrePersist / PreUpdate.
 *
 * Les relations proposedBy et createdBy sont nullables avec onDelete='SET NULL'
 * pour conserver l'activité si l'utilisateur est supprimé.
 */
#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Activity
{
    /** Activité visible sur le calendrier public. */
    public const STATUS_PUBLISHED = 'published';

    /** Proposition en attente de validation par un administrateur. */
    public const STATUS_PENDING   = 'pending';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    /**
     * Type d'activité : soirée JDS, JDR, GN, AG, etc.
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 16, options: ['default' => 'published'])]
    private string $status = self::STATUS_PUBLISHED;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $proposedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Lifecycle callback : initialise createdAt et updatedAt à la création.
     */
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Lifecycle callback : met à jour updatedAt à chaque modification.
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    /**
     * Accepte DateTimeInterface et normalise en DateTimeImmutable.
     */
    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt instanceof \DateTimeImmutable
            ? $startAt
            : \DateTimeImmutable::createFromInterface($startAt);
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @throws \InvalidArgumentException si le statut n'est pas une valeur autorisée.
     */
    public function setStatus(string $status): static
    {
        if (!in_array($status, [self::STATUS_PUBLISHED, self::STATUS_PENDING], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid status "%s". Allowed values: "%s", "%s".',
                $status,
                self::STATUS_PUBLISHED,
                self::STATUS_PENDING
            ));
        }
        $this->status = $status;
        return $this;
    }

    public function getProposedBy(): ?User
    {
        return $this->proposedBy;
    }

    public function setProposedBy(?User $proposedBy): static
    {
        $this->proposedBy = $proposedBy;
        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }
}
