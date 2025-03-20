<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EntrepriseRepository;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise')]
class Entreprise
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $secteur = null;

    public function getSecteur(): ?string
    {
        return $this->secteur;
    }

    public function setSecteur(?string $secteur): self
    {
        $this->secteur = $secteur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $matricule_fiscale = null;

    public function getMatricule_fiscale(): ?string
    {
        return $this->matricule_fiscale;
    }

    public function setMatricule_fiscale(?string $matricule_fiscale): self
    {
        $this->matricule_fiscale = $matricule_fiscale;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone_number = null;

    public function getPhone_number(): ?string
    {
        return $this->phone_number;
    }

    public function setPhone_number(?string $phone_number): self
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $phone_verified = null;

    public function isPhone_verified(): ?bool
    {
        return $this->phone_verified;
    }

    public function setPhone_verified(?bool $phone_verified): self
    {
        $this->phone_verified = $phone_verified;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Candidat::class, mappedBy: 'entreprise')]
    private Collection $candidats;

    /**
     * @return Collection<int, Candidat>
     */
    public function getCandidats(): Collection
    {
        if (!$this->candidats instanceof Collection) {
            $this->candidats = new ArrayCollection();
        }
        return $this->candidats;
    }

    public function addCandidat(Candidat $candidat): self
    {
        if (!$this->getCandidats()->contains($candidat)) {
            $this->getCandidats()->add($candidat);
        }
        return $this;
    }

    public function removeCandidat(Candidat $candidat): self
    {
        $this->getCandidats()->removeElement($candidat);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Departement::class, mappedBy: 'entreprise')]
    private Collection $departements;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
        $this->departements = new ArrayCollection();
    }

    /**
     * @return Collection<int, Departement>
     */
    public function getDepartements(): Collection
    {
        if (!$this->departements instanceof Collection) {
            $this->departements = new ArrayCollection();
        }
        return $this->departements;
    }

    public function addDepartement(Departement $departement): self
    {
        if (!$this->getDepartements()->contains($departement)) {
            $this->getDepartements()->add($departement);
        }
        return $this;
    }

    public function removeDepartement(Departement $departement): self
    {
        $this->getDepartements()->removeElement($departement);
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

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

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

}
