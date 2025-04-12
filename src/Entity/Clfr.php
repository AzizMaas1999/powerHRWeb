<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ClfrRepository;

#[ORM\Entity(repositoryClass: ClfrRepository::class)]
#[ORM\Table(name: 'clfr')]
class Clfr
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
    private ?string $adresse = null;

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    #[ORM\Column(name: 'numTel', type: 'string', nullable: true)]
    private ?string $numTel = null;

    public function getNumTel(): ?string
    {
        return $this->numTel;
    }

    public function setNumTel(?string $numTel): self
    {
        $this->numTel = $numTel;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'clfrs')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')]
    private ?Employe $employe = null;

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;
        return $this;
    }

    #[ORM\Column(name: 'photoPath', type: 'string', nullable: true)]
    private ?string $photoPath = null;

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): self
    {
        $this->photoPath = $photoPath;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'clfr')]
    private Collection $feedbacks;

    public function __construct()
    {
        $this->feedbacks = new ArrayCollection();
    }

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedbacks(): Collection
    {
        if (!$this->feedbacks instanceof Collection) {
            $this->feedbacks = new ArrayCollection();
        }
        return $this->feedbacks;
    }

    public function addFeedback(Feedback $feedback): self
    {
        if (!$this->getFeedbacks()->contains($feedback)) {
            $this->getFeedbacks()->add($feedback);
        }
        return $this;
    }

    public function removeFeedback(Feedback $feedback): self
    {
        $this->getFeedbacks()->removeElement($feedback);
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

}
