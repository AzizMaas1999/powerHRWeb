<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\RepquestionnaireRepository;

#[ORM\Entity(repositoryClass: RepquestionnaireRepository::class)]
#[ORM\Table(name: 'repquestionnaire')]
class Repquestionnaire
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
    #[Assert\NotBlank(message: "La réponse ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La réponse doit contenir au moins {{ limit }} caractères.",
       
    )]
    private ?string $reponse = null;
    

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): self
    {
        $this->reponse = $reponse;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $questionnaire_id = null;

    public function getQuestionnaire_id(): ?int
    {
        return $this->questionnaire_id;
    }

    public function setQuestionnaire_id(?int $questionnaire_id): self
    {
        $this->questionnaire_id = $questionnaire_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $employe_id = null;

    public function getEmploye_id(): ?int
    {
        return $this->employe_id;
    }

    public function setEmploye_id(?int $employe_id): self
    {
        $this->employe_id = $employe_id;
        return $this;
    }

    public function getQuestionnaireId(): ?int
    {
        return $this->questionnaire_id;
    }

    public function setQuestionnaireId(?int $questionnaire_id): static
    {
        $this->questionnaire_id = $questionnaire_id;
        return $this;
    }

    public function getEmployeId(): ?int
    {
        return $this->employe_id;
    }

    public function setEmployeId(?int $employe_id): static
    {
        $this->employe_id = $employe_id;
        return $this;
    }
}
