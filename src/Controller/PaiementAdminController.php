<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[IsGranted('ROLE_ADMIN')]
#[Route('/paiementadmin')]
final class PaiementAdminController extends AbstractController
{
    #[Route('/', name: 'app_paiementadmin_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository): Response
    {
        return $this->render('paiementadmin/index.html.twig', [
            'paiements' => $paiementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_paiementadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FactureRepository $factureRepository): Response
    {
        $paiement = new Paiement();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the date to current date if not provided
            if (!$paiement->getDatePaiement()) {
                $paiement->setDatePaiement(new \DateTime());
            }
            
            // Handle selected factures
            $selectedFactures = $form->get('factures')->getData();
            $totalAmount = 0;
            
            foreach ($selectedFactures as $facture) {
                $totalAmount += $facture->getMontant();
            }
            
            $paiement->setMontant($totalAmount);
            
            $entityManager->persist($paiement);
            
            // Update the factures with the payment ID and status
            foreach ($selectedFactures as $facture) {
                $facture->setPaiementId($paiement->getId());
                $facture->setStatus('Payée');
                $entityManager->persist($facture);
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Paiement effectué : %.2f TND',
                $paiement->getMontant()
            ));

            return $this->redirectToRoute('app_paiementadmin_index');
        }

        return $this->render('paiementadmin/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }
    
    #[Route('/unpaid-invoices', name: 'app_paiementadmin_unpaid_invoices', methods: ['GET'])]
    public function getUnpaidInvoices(FactureRepository $factureRepository): JsonResponse
    {
        $unpaidInvoices = $factureRepository->findBy(['status' => 'Non payée']);
        
        $formattedInvoices = [];
        foreach ($unpaidInvoices as $invoice) {
            $formattedInvoices[] = [
                'id' => $invoice->getId(),
                'reference' => $invoice->getReference(),
                'amount' => $invoice->getMontant(),
                'date' => $invoice->getDateCreation()->format('d/m/Y'),
                'client' => $invoice->getEntreprise() ? $invoice->getEntreprise()->getNom() : 'Non défini',
            ];
        }
        
        return new JsonResponse(['invoices' => $formattedInvoices]);
    }

    #[Route('/{id}', name: 'app_paiementadmin_show', methods: ['GET'])]
    public function show(Paiement $paiement, FactureRepository $factureRepository): Response
    {
        // Get all invoices associated with this payment
        $factures = $factureRepository->findBy(['paiementId' => $paiement->getId()]);
        
        return $this->render('paiementadmin/show.html.twig', [
            'paiement' => $paiement,
            'factures' => $factures
        ]);
    }
    
    #[Route('/{id}/receipt', name: 'app_paiementadmin_receipt', methods: ['GET'])]
    public function generateReceipt(Paiement $paiement, FactureRepository $factureRepository): Response
    {
        // Get all invoices associated with this payment
        $factures = $factureRepository->findBy(['paiementId' => $paiement->getId()]);
        
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($options);
        
        // Render the receipt template
        $html = $this->renderView('paiementadmin/receipt_pdf.html.twig', [
            'paiement' => $paiement,
            'factures' => $factures
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Stream the PDF
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition', 
            'attachment; filename="recu-paiement-' . $paiement->getId() . '.pdf"'
        );
        
        return $response;
    }

    #[Route('/{id}/edit', name: 'app_paiementadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Paiement mis à jour avec succès');
            return $this->redirectToRoute('app_paiementadmin_index');
        }

        return $this->render('paiementadmin/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiementadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager, FactureRepository $factureRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $paiement->getId(), $request->getPayload()->getString('_token'))) {
            // Update any invoices associated with this payment
            $factures = $factureRepository->findBy(['paiementId' => $paiement->getId()]);
            foreach ($factures as $facture) {
                $facture->setPaiementId(null);
                $facture->setStatus('Non payée');
                $entityManager->persist($facture);
            }
            
            $entityManager->remove($paiement);
            $entityManager->flush();
            
            $this->addFlash('success', 'Paiement supprimé avec succès');
        }

        return $this->redirectToRoute('app_paiementadmin_index');
    }
}