<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ArticleRepository;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $prixUni = null;

    public function getPrixUni(): ?float
    {
        return $this->prixUni;
    }

    public function setPrixUni(?float $prixUni): self
    {
        $this->prixUni = $prixUni;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $TVA = null;

    public function getTVA(): ?float
    {
        return $this->TVA;
    }

    public function setTVA(?float $TVA): self
    {
        $this->TVA = $TVA;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'facture_id', referencedColumnName: 'id')]
    private ?Facture $facture = null;

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
