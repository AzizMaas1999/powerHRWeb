<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PaieRepository;

#[ORM\Entity(repositoryClass: PaieRepository::class)]
#[ORM\Table(name: 'paie')]
class Paie
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

    #[ORM\Column(name: 'nbJour', type: 'integer', nullable: true)]
    private ?int $nbJour = null;

    public function getNbJour(): ?int
    {
        return $this->nbJour;
    }

    public function setNbJour(?int $nbJour): self
    {
        $this->nbJour = $nbJour;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $montant = null;

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $mois = null;

    public function getMois(): ?string
    {
        return $this->mois;
    }

    public function setMois(?string $mois): self
    {
        $this->mois = $mois;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $annee = null;

    public function getAnnee(): ?string
    {
        return $this->annee;
    }

    public function setAnnee(?string $annee): self
    {
        $this->annee = $annee;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Pointage::class, mappedBy: 'paie')]
    private Collection $pointages;

    public function __construct()
    {
        $this->pointages = new ArrayCollection();
    }

    /**
     * @return Collection<int, Pointage>
     */
    public function getPointages(): Collection
    {
        if (!$this->pointages instanceof Collection) {
            $this->pointages = new ArrayCollection();
        }
        return $this->pointages;
    }

    public function addPointage(Pointage $pointage): self
    {
        if (!$this->getPointages()->contains($pointage)) {
            $this->getPointages()->add($pointage);
        }
        return $this;
    }

    public function removePointage(Pointage $pointage): self
    {
        $this->getPointages()->removeElement($pointage);
        return $this;
    }

}
