<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\EntrepriseRepository;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Candidat;
use App\Entity\Departement;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise')]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 15,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le secteur est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 10,
        minMessage: 'Le secteur doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le secteur ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $secteur = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le matricule fiscale est obligatoire')]
    #[Assert\Length(
        min: 5,
        max: 20,
        minMessage: 'Le matricule fiscale doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le matricule fiscale ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9A-Z\-]+$/i',
        message: 'Le matricule fiscale ne peut contenir que des chiffres, des lettres et des tirets'
    )]
    private ?string $matricule_fiscale = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire')]
    #[Assert\Length(
        exactly: 8,
        exactMessage: 'Le numéro de téléphone doit contenir exactement {{ limit }} chiffres'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]{8}$/',
        message: 'Le numéro de téléphone doit contenir exactement 8 chiffres'
    )]
    private ?string $phone_number = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $phone_verified = null;

    #[ORM\OneToMany(targetEntity: Candidat::class, mappedBy: 'entreprise')]
    private Collection $candidats;

    #[ORM\OneToMany(targetEntity: Departement::class, mappedBy: 'entreprise')]
    private Collection $departements;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
        $this->departements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSecteur(): ?string
    {
        return $this->secteur;
    }

    public function setSecteur(?string $secteur): self
    {
        $this->secteur = $secteur;
        return $this;
    }

    public function getMatricule_fiscale(): ?string
    {
        return $this->matricule_fiscale;
    }

    public function setMatricule_fiscale(?string $matricule_fiscale): self
    {
        $this->matricule_fiscale = $matricule_fiscale;
        return $this;
    }

    public function getMatriculeFiscale(): ?string
    {
        return $this->matricule_fiscale;
    }

    public function setMatriculeFiscale(?string $matricule_fiscale): static
    {
        $this->matricule_fiscale = $matricule_fiscale;
        return $this;
    }

    public function getPhone_number(): ?string
    {
        return $this->phone_number;
    }

    public function setPhone_number(?string $phone_number): self
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function isPhone_verified(): ?bool
    {
        return $this->phone_verified;
    }

    public function setPhone_verified(?bool $phone_verified): self
    {
        $this->phone_verified = $phone_verified;
        return $this;
    }

    public function isPhoneVerified(): ?bool
    {
        return $this->phone_verified;
    }

    public function setPhoneVerified(?bool $phone_verified): static
    {
        $this->phone_verified = $phone_verified;
        return $this;
    }

    /**
     * @return Collection<int, Candidat>
     */
    public function getCandidats(): Collection
    {
        return $this->candidats;
    }

    public function addCandidat(Candidat $candidat): self
    {
        if (!$this->candidats->contains($candidat)) {
            $this->candidats->add($candidat);
        }
        return $this;
    }

    public function removeCandidat(Candidat $candidat): self
    {
        $this->candidats->removeElement($candidat);
        return $this;
    }

    /**
     * @return Collection<int, Departement>
     */
    public function getDepartements(): Collection
    {
        return $this->departements;
    }

    public function addDepartement(Departement $departement): self
    {
        if (!$this->departements->contains($departement)) {
            $this->departements->add($departement);
        }
        return $this;
    }

    public function removeDepartement(Departement $departement): self
    {
        $this->departements->removeElement($departement);
        return $this;
    }
}
