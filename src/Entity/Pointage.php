<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PointageRepository;

#[ORM\Entity(repositoryClass: PointageRepository::class)]
#[ORM\Table(name: 'pointage')]
class Pointage
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
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    private ?string $heureEntree = null;

    public function getHeureEntree(): ?string
    {
        return $this->heureEntree;
    }

    public function setHeureEntree(string $heureEntree): self
    {
        $this->heureEntree = $heureEntree;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    private ?string $heureSortie = null;

    public function getHeureSortie(): ?string
    {
        return $this->heureSortie;
    }

    public function setHeureSortie(string $heureSortie): self
    {
        $this->heureSortie = $heureSortie;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'pointages')]
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

    #[ORM\ManyToOne(targetEntity: Paie::class, inversedBy: 'pointages')]
    #[ORM\JoinColumn(name: 'paie_id', referencedColumnName: 'id')]
    private ?Paie $paie = null;

    public function getPaie(): ?Paie
    {
        return $this->paie;
    }

    public function setPaie(?Paie $paie): self
    {
        $this->paie = $paie;
        return $this;
    }

}
