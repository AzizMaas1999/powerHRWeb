<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\CandidatRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidat')]
#[Vich\Uploadable]
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
    #[Assert\NotBlank(message: "Le nom est requis.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/", 
        message: "Le nom ne peut contenir que des lettres."
    )]
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
    #[Assert\NotBlank(message: "Le prénom est requis.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/", 
        message: "Le prénom ne peut contenir que des lettres."
    )]
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
    #[Assert\NotBlank(message: "L'email est requis.")]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]
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
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis.")]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}$/",
        message: "Le numéro de téléphone doit contenir exactement 8 chiffres."
    )]
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

    #[Vich\UploadableField(mapping: 'candidat_cv', fileNameProperty: 'cvPdfUrl')]
    private ?File $cvFile = null;

    #[ORM\Column(name: 'cvPdfUrl', type: 'string', nullable: true)]
    private ?string $cvPdfUrl = null;

    public function setCvFile(?File $file = null): void
    {
        $this->cvFile = $file;
        if (null !== $file) {
            // Use getFilename() which exists on File class instead of getClientOriginalName()
            $this->cvPdfUrl = $file->getFilename();
        }
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function getCvPdfUrl(): ?string
    {
        return $this->cvPdfUrl;
    }

    public function setCvPdfUrl(?string $cvPdfUrl): self
    {
        $this->cvPdfUrl = $cvPdfUrl;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'candidats')]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "Veuillez sélectionner une entreprise.")]
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