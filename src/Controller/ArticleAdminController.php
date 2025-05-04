<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Facture;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/articleadmin')]
final class ArticleAdminController extends AbstractController
{
    #[Route('/', name: 'app_articleadmin_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('articleadmin/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_articleadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            // Mise à jour du montant total de la facture associée
            if ($article->getFacture()) {
                $this->updateFactureTotal($article->getFacture(), $entityManager);
            }

            $this->addFlash('success', 'Article ajouté avec succès.');
            return $this->redirectToRoute('app_articleadmin_index');
        }

        return $this->render('articleadmin/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_articleadmin_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('articleadmin/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_articleadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $oldFacture = $article->getFacture();
        $oldItemTotal = $article->getQuantity() * $article->getPrixUni() * (1 + $article->getTVA() / 100);
        
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            // Mise à jour du montant total de l'ancienne facture si elle existe
            if ($oldFacture) {
                $this->updateFactureTotal($oldFacture, $entityManager);
            }
            
            // Mise à jour du montant total de la nouvelle facture si elle est différente
            $newFacture = $article->getFacture();
            if ($newFacture && ($newFacture !== $oldFacture)) {
                $this->updateFactureTotal($newFacture, $entityManager);
            }

            $this->addFlash('success', 'Article mis à jour avec succès.');
            return $this->redirectToRoute('app_articleadmin_index');
        }

        return $this->render('articleadmin/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_articleadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $facture = $article->getFacture();
            
            $entityManager->remove($article);
            $entityManager->flush();
            
            // Mise à jour du montant total de la facture associée
            if ($facture) {
                $this->updateFactureTotal($facture, $entityManager);
            }
            
            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('app_articleadmin_index');
    }
    
    #[Route('/facture/{id}/articles', name: 'app_articleadmin_by_facture', methods: ['GET'])]
    public function articlesByFacture(Facture $facture): Response
    {
        return $this->render('articleadmin/by_facture.html.twig', [
            'articles' => $facture->getArticles(),
            'facture' => $facture
        ]);
    }
    
    /**
     * Méthode utilitaire pour mettre à jour le montant total d'une facture
     */
    private function updateFactureTotal(Facture $facture, EntityManagerInterface $entityManager): void
    {
        $articles = $facture->getArticles();
        $totalHT = 0;
        $totalTTC = 0;
        
        foreach ($articles as $article) {
            $articleHT = $article->getQuantity() * $article->getPrixUni();
            $articleTTC = $articleHT * (1 + $article->getTVA() / 100);
            
            $totalHT += $articleHT;
            $totalTTC += $articleTTC;
        }
        
        $facture->setTotal($totalTTC);
        $entityManager->flush();
    }
}