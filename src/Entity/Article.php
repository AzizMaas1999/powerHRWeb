<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

    #[ORM\Column(name: "prixUni", type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le prix unitaire est requis.")]
    #[Assert\Positive(message: "Le prix unitaire doit être un nombre positif.")]
    #[Assert\Type(type: 'float', message: "Le prix unitaire doit être un nombre.")]
    private ?float $prixUni = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "La TVA est requise.")]
    #[Assert\PositiveOrZero(message: "La TVA doit être un nombre positif ou zéro.")]
    #[Assert\Type(type: 'float', message: "La TVA doit être un nombre.")]
    private ?float $TVA = null;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'facture_id', referencedColumnName: 'id')]
    private ?Facture $facture = null;

    // Getters & Setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrixUni(): ?float
    {
        return $this->prixUni;
    }

    public function setPrixUni(?float $prixUni): self
    {
        $this->prixUni = $prixUni;
        return $this;
    }

    public function getTVA(): ?float
    {
        return $this->TVA;
    }

    public function setTVA(?float $TVA): self
    {
        $this->TVA = $TVA;
        return $this;
    }

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): self
    {
        $this->facture = $facture;
        return $this;
    }
}
