<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Enum\Poste;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\EmployeRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')] 
class Employe implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le username est requis.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/",
        message: "Le username ne peut contenir que des lettres."
    )]
    private ?string $username = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message:'Le password est requis.')]
    private ?string $password = null;

    #[ORM\Column(type: 'string', enumType: Poste::class)]
    #[Assert\NotBlank(message:'Le poste est requis.')]
    private Poste $poste;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotBlank(message:'Le salaire est requis.')]
    #[Assert\Regex(
        pattern: "/^[0-9]$/",
        message: "Le salaire ne peut contenir que des chiffres."
    )]
    private ?float $salaire = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le rib est requis.')]
    #[Assert\Regex(
        pattern: "/^[0-9]{15}$/", 
        message: "Le rib doit contenir exactement 15 chiffres."
    )]
    private ?string $rib = null;

    #[ORM\Column(name: 'codeSociale', type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le code sociale est requis.')]
    #[Assert\Regex(
        pattern: "/^[0-9]{4}$/", 
        message: "Le codeSociale doit contenir exactement 4 chiffres."
    )]
    private ?string $codeSociale = null;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'employes')]
    #[ORM\JoinColumn(name: 'departement_id', referencedColumnName: 'id')]
    private ?Departement $departement = null;

    #[ORM\OneToMany(targetEntity: Clfr::class, mappedBy: 'employe')]
    private Collection $clfrs;

    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'employe')]
    private Collection $demandes;

    #[ORM\OneToOne(targetEntity: FicheEmploye::class, mappedBy: 'employe')]
    private ?FicheEmploye $ficheEmploye = null;

    #[ORM\OneToMany(targetEntity: Pointage::class, mappedBy: 'employe')]
    private Collection $pointages;

    #[ORM\OneToMany(targetEntity: Questionnaire::class, mappedBy: 'employe')]
    private Collection $questionnaires;

    public function __construct()
    {
        $this->clfrs = new ArrayCollection();
        $this->demandes = new ArrayCollection();
        $this->pointages = new ArrayCollection();
        $this->questionnaires = new ArrayCollection();
    }

    // Getter and Setter for ID
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username; 
    }

    public function getRoles(): array
    {
        return match($this->poste) {
            Poste::ADMIN => ['ROLE_ADMIN'],
            Poste::DIRECTEUR => ['ROLE_DIRECTEUR'],
            Poste::FACTURATION => ['ROLE_FACTURATION'],
            Poste::OUVRIER => ['ROLE_OUVRIER'],
            Poste::CHARGES => ['ROLE_CHARGES'],
            default => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void
    {
        $this->password = null; 
    }

    public function getPoste(): Poste
    {
        return $this->poste;
    }

    public function setPoste(Poste $poste): self
    {
        $this->poste = $poste;
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

    public function getRib(): ?string
    {
        return $this->rib;
    }

    public function setRib(?string $rib): self
    {
        $this->rib = $rib;
        return $this;
    }

    public function getCodeSociale(): ?string
    {
        return $this->codeSociale;
    }

    public function setCodeSociale(?string $codeSociale): self
    {
        $this->codeSociale = $codeSociale;
        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    public function getClfrs(): Collection
    {
        return $this->clfrs;
    }

    public function addClfr(Clfr $clfr): self
    {
        if (!$this->clfrs->contains($clfr)) {
            $this->clfrs->add($clfr);
        }
        return $this;
    }

    public function removeClfr(Clfr $clfr): self
    {
        $this->clfrs->removeElement($clfr);
        return $this;
    }

    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): self
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
        }
        return $this;
    }

    public function removeDemande(Demande $demande): self
    {
        $this->demandes->removeElement($demande);
        return $this;
    }

    public function getFicheEmploye(): ?FicheEmploye
    {
        return $this->ficheEmploye;
    }

    public function setFicheEmploye(?FicheEmploye $ficheEmploye): self
    {
        $this->ficheEmploye = $ficheEmploye;
        return $this;
    }

    public function getPointages(): Collection
    {
        return $this->pointages;
    }

    public function addPointage(Pointage $pointage): self
    {
        if (!$this->pointages->contains($pointage)) {
            $this->pointages->add($pointage);
        }
        return $this;
    }

    public function removePointage(Pointage $pointage): self
    {
        $this->pointages->removeElement($pointage);
        return $this;
    }

    public function getQuestionnaires(): Collection
    {
        return $this->questionnaires;
    }

    public function addQuestionnaire(Questionnaire $questionnaire): self
    {
        if (!$this->questionnaires->contains($questionnaire)) {
            $this->questionnaires->add($questionnaire);
        }
        return $this;
    }

    public function removeQuestionnaire(Questionnaire $questionnaire): self
    {
        $this->questionnaires->removeElement($questionnaire);
        return $this;
    }
}
