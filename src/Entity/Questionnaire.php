<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\QuestionnaireRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionnaireRepository::class)]
#[ORM\Table(name: 'questionnaire')]
class Questionnaire
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

    #[ORM\Column(name: "dateCreation", type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateCreation = null;

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'champs obligatoire')]
    #[Assert\Length(
        min: 10,
        minMessage: "L'objet doit contenir au moins 10 caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u',
        message: "L'objet ne doit contenir que des lettres et des espaces."
    )]
    private ?string $objet = null;

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(?string $objet): self
    {
        $this->objet = $objet;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'champs obligatoire')]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins 10 caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u',
        message: "La description ne doit contenir que des lettres et des espaces."
    )]
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

    #[ORM\ManyToOne(targetEntity: Employe::class, inversedBy: 'questionnaires')]
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
}
