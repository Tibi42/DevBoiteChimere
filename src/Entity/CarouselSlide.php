<?php

namespace App\Entity;

use App\Repository\CarouselSlideRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarouselSlideRepository::class)]
#[ORM\Table(name: 'carousel_slide')]
class CarouselSlide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(length: 128)]
    private ?string $tag = null;

    /**
     * Classe CSS pour la couleur du tag (ex: text-custom-orange, text-cyan-400).
     */
    #[ORM\Column(length: 64)]
    private ?string $tagColor = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 128)]
    private ?string $date = null;

    #[ORM\Column(length: 64)]
    private ?string $btnText = null;

    /**
     * Classes CSS du bouton (ex: bg-custom-orange group-hover:bg-orange-600).
     */
    #[ORM\Column(length: 255)]
    private ?string $btnClass = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $btnUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTagColor(): ?string
    {
        return $this->tagColor;
    }

    public function setTagColor(string $tagColor): static
    {
        $this->tagColor = $tagColor;
        return $this;
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

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getBtnText(): ?string
    {
        return $this->btnText;
    }

    public function setBtnText(string $btnText): static
    {
        $this->btnText = $btnText;
        return $this;
    }

    public function getBtnClass(): ?string
    {
        return $this->btnClass;
    }

    public function setBtnClass(string $btnClass): static
    {
        $this->btnClass = $btnClass;
        return $this;
    }

    public function getBtnUrl(): ?string
    {
        return $this->btnUrl;
    }

    public function setBtnUrl(?string $btnUrl): static
    {
        $this->btnUrl = $btnUrl;
        return $this;
    }
}
