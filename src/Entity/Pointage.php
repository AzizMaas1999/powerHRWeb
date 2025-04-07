<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotNull(message: "La date est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\LessThanOrEqual("today", message: "La date ne peut pas être supérieure à aujourd'hui.")]
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

    #[ORM\Column(name: 'heureEntree', type: 'time', nullable: false)]
    #[Assert\NotNull(message: "L'heure d'entrée est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $heureEntree = null;

    public function getHeureEntree(): ?\DateTimeInterface
    {
        return $this->heureEntree;
    }

    public function setHeureEntree(?\DateTimeInterface $heureEntree): self
    {
        $this->heureEntree = $heureEntree;
        return $this;
    }

    #[ORM\Column(name: 'heureSortie', type: 'time', nullable: false)]
    #[Assert\NotNull(message: "L'heure de sortie est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\Expression(
        "this.getHeureSortie() > this.getHeureEntree()",
        message: "L'heure de sortie doit être après l'heure d'entrée."
    )]
    private ?\DateTimeInterface $heureSortie = null;

    public function getHeureSortie(): ?\DateTimeInterface
    {
        return $this->heureSortie;
    }

    public function setHeureSortie(?\DateTimeInterface $heureSortie): self
    {
        $this->heureSortie = $heureSortie;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'pointages')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "L'employé est obligatoire.")]
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
    #[Assert\NotNull(message: "La paie est obligatoire.")]
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
