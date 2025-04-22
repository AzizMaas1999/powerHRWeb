<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // Ajouter l'importation des contraintes Symfony
use App\Repository\PaiementRepository;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
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

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: "La date de paiement est obligatoire.")]
    #[Assert\Type(type: \DateTimeInterface::class, message: "La date doit être une date valide.")]
    #[Assert\LessThanOrEqual("today", message: "La date de paiement ne peut pas être dans le futur.")]
    private ?\DateTimeInterface $date = null;
    


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Assert\NotBlank(message: "Le mode de paiement est obligatoire.")]
    #[Assert\Choice(
        choices: ['Virement', 'Chèque', 'espece', 'traite'],
        message: "Le mode de paiement doit être l'un des choix suivants : Virement, Chèque, espece, traite."
    )]
    private ?string $mode = null;
    

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(?string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "La référence est obligatoire.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "La référence doit comporter au moins 3 caractères.", maxMessage: "La référence ne peut pas dépasser 255 caractères.")]
    private ?string $reference = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "Le montant est obligatoire.")]
    #[Assert\Positive(message: "Le montant doit être un nombre positif.")]
    #[Assert\Type(type: 'float', message: "Le montant doit être un nombre valide.")]
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
    
}
