<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\FicheEmployeRepository;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: FicheEmployeRepository::class)]
#[ORM\Table(name: 'fiche_employe')]

/**
 * @Vich\Uploadable()
 */
class FicheEmploye
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
    #[Assert\NotBlank(message: "Le cin est requis.")]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}+$/", 
        message: "Le cin ne peut contenir que des chiffres."
    )]
    private ?string $cin = null;

    public function getCin(): ?string
    {
        return $this->cin;
    }

    public function setCin(string $cin): self
    {
        $this->cin = $cin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message:'Le nom est requis.')]
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
    #[Assert\NotBlank(message:'Le prenom est requis.')]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/",
        message: "Le prenom ne peut contenir que des lettres."
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "L'adresse est requis.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/", 
        message: "L'adresse ne peut contenir que des lettres."
    )]
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

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Le city est requis.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Zà-ÿÀ-ß]+$/",
        message: "Le city ne peut contenir que des lettres."
    )]
    private ?string $city = null;

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le zip est requis.')]
    #[Assert\Regex(
        pattern: "/^[0-9]{5}$/",
        message:"Le zip doit contenir exactement 5 chiffres."
    )]
    private ?string $zip = null;

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;
        return $this;
    }

    #[ORM\Column(name: 'numTel', type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis.")]
    #[Assert\Regex(
        pattern: "/^[0-9]{8}$/",
        message: "Le numéro de téléphone doit contenir exactement 8 chiffres."
    )]
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
    #[Vich\UploadableField(mapping: 'fiche_cv_pdf', fileNameProperty: 'cvPdfUrl')]


    #[ORM\Column(name: 'cvPdfUrl', type: 'string', nullable: true)]
    #[Assert\NotBlank(message:'Le CV est requis.')]
    
    private ?string $cvPdfUrl = null;

    public function getCvPdfUrl(): ?string
    {
        return $this->cvPdfUrl;
    }

    public function setCvPdfUrl(?string $cvPdfUrl): self
    {
        $this->cvPdfUrl = $cvPdfUrl;
        return $this;
    }
    #[Vich\Uploadable]
    private ?File $cvPdfFile = null;

public function setCvPdfFile(?File $file = null): void
{
    $this->cvPdfFile = $file;

    // Force Vich to detect a change without updating DB
    if ($file !== null) {
        // Just change any existing property (for example: cvPdfUrl = itself)
        $this->cvPdfUrl = $this->cvPdfUrl;
    }
}


public function getCvPdfFile(): ?File
{
    return $this->cvPdfFile;
}


    #[ORM\OneToOne(targetEntity: Employe::class, inversedBy: 'ficheEmploye')]
    #[ORM\JoinColumn(name: 'employe_id', referencedColumnName: 'id', unique: true)]
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
