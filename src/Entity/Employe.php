<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\EmployeRepository;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')]
class Employe implements UserInterface, PasswordAuthenticatedUserInterface
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
    #[Assert\NotBlank(message:'Le username est requis.')]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/",
        message: "Le nom ne peut contenir que des lettres."
    )]
    private ?string $username = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /* Email field removed - now only in FicheEmploye entity */

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le mot de passe est requis.")]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message:'Le poste est requis.')]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/",
        message: "Le nom ne peut contenir que des lettres."
    )]
    private ?string $poste = null;

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(string $poste): self
    {
        $this->poste = $poste;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotBlank(message:'Le salaire est requis.')]
    #[Assert\Regex(
        pattern: "/^(?!0+(\.0+)?$)[0-9]+(\.[0-9]{1,2})?$/",
        message: "Le salaire doit être un nombre positif"
    )]
    private ?float $salaire = null;

    public function getSalaire(): ?float
    {
        return $this->salaire;
    }

    public function setSalaire(?float $salaire): self
    {
        $this->salaire = $salaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le rib est requis.')]
    #[Assert\Regex(
        pattern: "/^[0-9]{15}+$/", 
        message: "Le rib ne peut contenir que des chiffres."
    )]
    private ?string $rib = null;

    public function getRib(): ?string
    {
        return $this->rib;
    }

    public function setRib(?string $rib): self
    {
        $this->rib = $rib;
        return $this;
    }

    #[ORM\Column(name: 'codeSociale', type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le code sociale est requis.')]
   
    #[Assert\Regex(
        pattern: "/^[0-9]{4}+$/", 
        message: "Le code sociale ne peut contenir que des chiffres."
    )]
    private ?string $codeSociale = null;

    public function getCodeSociale(): ?string
    {
        return $this->codeSociale;
    }

    public function setCodeSociale(?string $codeSociale): self
    {
        $this->codeSociale = $codeSociale;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'employes')]
    #[ORM\JoinColumn(name: 'departement_id', referencedColumnName: 'id')]
    #[Assert\NotBlank(message:'Le departement est requis.')]
    private ?Departement $departement = null;

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Clfr::class, mappedBy: 'employe')]
    private Collection $clfrs;

    /**
     * @return Collection<int, Clfr>
     */
    public function getClfrs(): Collection
    {
        if (!$this->clfrs instanceof Collection) {
            $this->clfrs = new ArrayCollection();
        }
        return $this->clfrs;
    }

    public function addClfr(Clfr $clfr): self
    {
        if (!$this->getClfrs()->contains($clfr)) {
            $this->getClfrs()->add($clfr);
        }
        return $this;
    }

    public function removeClfr(Clfr $clfr): self
    {
        $this->getClfrs()->removeElement($clfr);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'employe')]
    private Collection $demandes;

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        if (!$this->demandes instanceof Collection) {
            $this->demandes = new ArrayCollection();
        }
        return $this->demandes;
    }

    public function addDemande(Demande $demande): self
    {
        if (!$this->getDemandes()->contains($demande)) {
            $this->getDemandes()->add($demande);
        }
        return $this;
    }

    public function removeDemande(Demande $demande): self
    {
        $this->getDemandes()->removeElement($demande);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: FicheEmploye::class, mappedBy: 'employe')]
    private ?FicheEmploye $ficheEmploye = null;

    public function getFicheEmploye(): ?FicheEmploye
    {
        return $this->ficheEmploye;
    }

    public function setFicheEmploye(?FicheEmploye $ficheEmploye): self
    {
        $this->ficheEmploye = $ficheEmploye;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Pointage::class, mappedBy: 'employe')]
    private Collection $pointages;

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

    #[ORM\OneToMany(targetEntity: Questionnaire::class, mappedBy: 'employe')]
    private Collection $questionnaires;

    public function __construct()
    {
        $this->clfrs = new ArrayCollection();
        $this->demandes = new ArrayCollection();
        $this->pointages = new ArrayCollection();
        $this->questionnaires = new ArrayCollection();
    }

    /**
     * @return Collection<int, Questionnaire>
     */
    public function getQuestionnaires(): Collection
    {
        if (!$this->questionnaires instanceof Collection) {
            $this->questionnaires = new ArrayCollection();
        }
        return $this->questionnaires;
    }

    public function addQuestionnaire(Questionnaire $questionnaire): self
    {
        if (!$this->getQuestionnaires()->contains($questionnaire)) {
            $this->getQuestionnaires()->add($questionnaire);
        }
        return $this;
    }

    public function removeQuestionnaire(Questionnaire $questionnaire): self
    {
        $this->getQuestionnaires()->removeElement($questionnaire);
        return $this;
    }

    public function getRoles(): array
    {
        return match ($this->getPoste()) {
            'Admin' => ['ROLE_ADMIN'],
            'Directeur' => ['ROLE_DIRECTEUR'],
            'Charges' => ['ROLE_CHARGES'],
            'Facturation' => ['ROLE_FACTURATION'],
            'Ouvrier' => ['ROLE_OUVRIER'],
        };
    }

    public function getSalt(): ?string
    {
        return null; // bcrypt does not require a separate salt
    }
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }


}