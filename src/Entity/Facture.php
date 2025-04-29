<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FactureRepository;
use Symfony\Component\Validator\Constraints as Assert; // 🔸 Ajout ici

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\Table(name: 'facture')]
class Facture
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
    
    public function __toString(): string
{
    return 'Facture n°' . $this->getNum(); // ou toute autre propriété représentative
}


#[ORM\Column(type: 'date', nullable: false)]
#[Assert\NotBlank(message: "La date est requise.")]
#[Assert\LessThanOrEqual("today", message: "La date ne peut pas être dans le futur.")]
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

    #[ORM\Column(name: 'typeFact', type: 'string', nullable: true)]
    private ?string $typeFact = null;

    public function getTypeFact(): ?string
    {
        return $this->typeFact;
    }

    public function setTypeFact(?string $typeFact): self
    {
        $this->typeFact = $typeFact;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le numéro est requis.")]
    #[Assert\Regex(
        pattern: "/^\d+$/",
        message: "Le numéro doit contenir uniquement des chiffres."
    )]
    private ?string $num = null;

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(string $num): self
    {
        $this->num = $num;
        return $this;
    }

    
#[ORM\Column(type: 'float', nullable: true)]
#[Assert\Regex(
    pattern: "/^\d+([.,]\d+)?$/",
    message: "Le total doit contenir uniquement des chiffres ou des décimales."
)]
private ?float $total = null;

public function getTotal(): ?float
{
    return $this->total;
}

public function setTotal(?float $total): self
{
    $this->total = $total;
    return $this;
}

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $clfr_id = null;

    public function getClfr_id(): ?int
    {
        return $this->clfr_id;
    }

    public function setClfr_id(?int $clfr_id): self
    {
        $this->clfr_id = $clfr_id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $paiement_id = null;

    public function getPaiement_id(): ?int
    {
        return $this->paiement_id;
    }

    public function setPaiement_id(?int $paiement_id): self
    {
        $this->paiement_id = $paiement_id;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'facture')]
    private Collection $articles;
    

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        if (!$this->articles instanceof Collection) {
            $this->articles = new ArrayCollection();
        }
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->getArticles()->contains($article)) {
            $this->getArticles()->add($article);
        }
        return $this;
    }

    public function removeArticle(Article $article): self
    {
        $this->getArticles()->removeElement($article);
        return $this;
    }

    public function getClfrId(): ?int
    {
        return $this->clfr_id;
    }

    public function setClfrId(?int $clfr_id): static
    {
        $this->clfr_id = $clfr_id;
        return $this;
    }

    public function getPaiementId(): ?int
    {
        return $this->paiement_id;
    }

    public function setPaiementId(?int $paiement_id): static
    {
        $this->paiement_id = $paiement_id;
        return $this;
    }





    // Dans ton entité Facture

public function calculerTotal(): void
{
    $total = 0.0;

    foreach ($this->getArticles() as $article) {
        $prixUnitaire = $article->getPrixUni();
        $quantite = $article->getQuantity();
        $tva = $article->getTVA() / 100;  // TVA sous forme de pourcentage

        // Calcul du prix total de l'article (prix unitaire * quantité * (1 + taux de TVA))
        $total += ($prixUnitaire * $quantite * (1 + $tva));
    }

    // Assigner le total à l'entité Facture
    $this->setTotal($total);
}




}

