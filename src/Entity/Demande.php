<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
#[ORM\Table(name: 'demande')]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: "dateCreation", type: 'date', nullable: false)]
    #[Assert\NotNull(message: "La date de création est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le type de demande est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le type ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $type = null;

    #[ORM\Column(name: "dateDebut", type: 'date', nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\LessThanOrEqual(
        propertyPath: "dateFin",
        message: "La date de début doit être antérieure ou égale à la date de fin."
    )]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: "dateFin", type: 'date', nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le salaire doit être un nombre positif ou nul.")]
    private ?float $salaire = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "La cause  est obligatoire.")]

    #[Assert\Length(
        min: 10,
        maxMessage: "La cause doit contenir au moins 10 caractères."
    )]
    private ?string $cause = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(
        choices: ["En Attente", "acceptée", "refusée"],
        message: "Le statut doit être 'en attente', 'acceptée' ou 'refusée'."
    )]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'demandes')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')]
    private ?Employe $employe = null;
 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getSalaire(): ?float
    {
        return $this->salaire;
    }

    public function setSalaire(?float $salaire): self
    {
        $this->salaire = $salaire;
        return $this;
    }

    public function getCause(): ?string
    {
        return $this->cause;
    }

    public function setCause(?string $cause): self
    {
        $this->cause = $cause;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;
        return $this;
    }
}
