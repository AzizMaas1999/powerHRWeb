<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\DepartementRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Entreprise;
use App\Entity\Employe;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
#[ORM\Table(name: 'departement')]
#[UniqueEntity(
    fields: ['nom', 'entreprise'],
    message: 'Un département avec ce nom existe déjà dans cette entreprise.',
    errorPath: 'nom'
)]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom du département est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 30,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ0-9\s\-]+$/',
        message: 'Le nom ne peut contenir que des lettres, des chiffres, des espaces et des tirets.'
    )]
    #[Assert\NotNull(message: 'Le nom du département ne peut pas être vide.')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Length(
        max: 120,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ0-9\s\-\.,\'\"]+$/',
        message: 'La description ne peut contenir que des lettres, des chiffres, des espaces et des caractères de ponctuation basiques.'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'departements')]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner une entreprise.')]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(targetEntity: Employe::class, mappedBy: 'departement')]
    private Collection $employes;

    public function __construct()
    {
        $this->employes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployes(): Collection
    {
        return $this->employes;
    }

    public function addEmploye(Employe $employe): self
    {
        if (!$this->employes->contains($employe)) {
            $this->employes->add($employe);
        }
        return $this;
    }

    public function removeEmploye(Employe $employe): self
    {
        $this->employes->removeElement($employe);
        return $this;
    }
}
