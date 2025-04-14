<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotNull(message: "Le nombre de jours est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 30,
        notInRangeMessage: "Le nombre de jours doit être entre {{ min }} et {{ max }}."
    )]
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
    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\Positive(message: "Le montant doit être un nombre positif.")]
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
    #[Assert\NotBlank(message: "Le mois est obligatoire.")]
    #[Assert\Choice(
        choices: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
        message: "Le mois doit être un nom valide."
    )]
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
    #[Assert\NotBlank(message: "L'année est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^\d{4}$/",
        message: "L'année doit être un nombre à 4 chiffres."
    )]
    #[Assert\LessThanOrEqual(
        value: 2025,
        message: "L'année ne peut pas être supérieure à l'année en cours."
    )]
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
