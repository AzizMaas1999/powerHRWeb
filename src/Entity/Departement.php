<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DepartementRepository;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
#[ORM\Table(name: 'departement')]
class Departement
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

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'departements')]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id')]
    private ?Entreprise $entreprise = null;

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Employe::class, mappedBy: 'departement')]
    private Collection $employes;

    public function __construct()
    {
        $this->employes = new ArrayCollection();
    }

    /**
     * @return Collection<int, Employe>
     */
    public function getEmployes(): Collection
    {
        if (!$this->employes instanceof Collection) {
            $this->employes = new ArrayCollection();
        }
        return $this->employes;
    }

    public function addEmploye(Employe $employe): self
    {
        if (!$this->getEmployes()->contains($employe)) {
            $this->getEmployes()->add($employe);
        }
        return $this;
    }

    public function removeEmploye(Employe $employe): self
    {
        $this->getEmployes()->removeElement($employe);
        return $this;
    }

}
