<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Form\FactureType;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Repository\ArticleRepository;
use App\Entity\Article;
use App\Form\ArticleType;
use Dompdf\Dompdf;
use Dompdf\Options;


#[Route('/facture')]
final class FactureController extends AbstractController
{
    // Afficher la liste des factures
    #[Route(name: 'app_facture_index', methods: ['GET'])]
    public function index(FactureRepository $factureRepository): Response
    {
        return $this->render('facture/index.html.twig', [
            'factures' => $factureRepository->findAll(),
        ]);
    }

    // Créer une nouvelle facture
    #[Route('/new', name: 'app_facture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);
        $articles = $entityManager->getRepository(Article::class)->findAll();

        // Créer un tableau clé = ID, valeur = Article
        $articlesArray = [];
        foreach ($articles as $article) {
            $articlesArray[$article->getId()] = $article;
        }
        



        if ($form->isSubmitted() && $form->isValid()) {
            // Calculer le total des articles associés à la facture
            $facture->calculerTotal();  // Appel de la méthode pour calculer le total à partir des articles
            
            $entityManager->persist($facture);
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/new.html.twig', [
            'facture' => $facture,
            'form' => $form,
            'articlesList' => $articlesArray, // <-- pas $articles simple

        ]);
    }

    // Afficher les détails d'une facture
    #[Route('/{id}', name: 'app_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    // Modifier une facture existante
    #[Route('/{id}/edit', name: 'app_facture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la facture
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/edit.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }

    // Supprimer une facture
    #[Route('/{id}', name: 'app_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $facture->getId(), $request->get('token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
    }

    // Effectuer le paiement Stripe pour une facture
    #[Route('/{id}/payer', name: 'app_facture_paiement', methods: ['GET'])]
    public function payerFacture(Facture $facture): Response
    {
        // Assurez-vous que la clé API Stripe est chargée correctement depuis les variables d'environnement
        $key = getenv('STRIPE_SECRET_KEY');
Stripe::setApiKey($key);

        
        // Initialiser Stripe
        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Facture #'.$facture->getNum(),
                    ],
                    'unit_amount' => $facture->getTotal() * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_facture_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_facture_show', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        
        // Ajouter un log pour l'ID de session
        error_log('Stripe session ID: ' . $checkoutSession->id);
        

        // Rediriger l'utilisateur vers la page Stripe pour le paiement
        return new RedirectResponse($checkoutSession->url);
    }


    #[Route('/facture/{id}/pdf', name: 'app_facture_pdf')]
    public function generatePdf(Facture $facture): Response
    {
        // Configuration de base
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
    
        // Instancier Dompdf avec les options
        $dompdf = new Dompdf($pdfOptions);
    
        // Générer le HTML
        $html = $this->renderView('facture/pdf.html.twig', [
            'facture' => $facture,
        ]);
    
        $dompdf->loadHtml($html);
    
        // (optionnel) Format du papier : A4 portrait
        $dompdf->setPaper('A4', 'portrait');
    
        // Génération du PDF
        $dompdf->render();
    
        // Envoi du PDF au navigateur
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture_'.$facture->getNum().'.pdf"',
            ]
        );
    }
}
