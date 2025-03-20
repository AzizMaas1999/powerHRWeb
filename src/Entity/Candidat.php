<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CandidatRepository;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidat')]
class Candidat
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $telephone = null;

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $cvPdfUrl = null;

    public function getCvPdfUrl(): ?string
    {
        return $this->cvPdfUrl;
    }

    public function setCvPdfUrl(string $cvPdfUrl): self
    {
        $this->cvPdfUrl = $cvPdfUrl;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'candidats')]
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

}
