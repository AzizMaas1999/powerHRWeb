<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FactureRepository;

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

    #[ORM\Column(type: 'string', nullable: true)]
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

}
