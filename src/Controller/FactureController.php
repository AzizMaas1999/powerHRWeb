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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Entity\Article;
use App\Entity\Paiement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use GuzzleHttp\Client;



#[IsGranted('ROLE_FACTURATION')]
#[Route('/facture')]
final class FactureController extends AbstractController
{
    #[Route(name: 'app_facture_index', methods: ['GET'])]
    public function index(FactureRepository $factureRepository): Response
    {
        return $this->render('facture/index.html.twig', [
            'factures' => $factureRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_facture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        $articles = $entityManager->getRepository(Article::class)->findAll();
        $articlesArray = [];
        foreach ($articles as $article) {
            $articlesArray[$article->getId()] = $article;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $facture->calculerTotal();
            $entityManager->persist($facture);
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/new.html.twig', [
            'facture' => $facture,
            'form' => $form,
            'articlesList' => $articlesArray,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_facture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $facture->calculerTotal();
            $entityManager->flush();

            return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facture/edit.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $facture->getId(), $request->request->get('_token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/payer', name: 'app_facture_paiement', methods: ['GET'])]
    public function payerFacture(Facture $facture, EntityManagerInterface $entityManager): Response
    {
        // Get environment variables from both $_SERVER and $_ENV
        $appToken = $_SERVER['FLOUCI_APP_TOKEN'] ?? $_ENV['FLOUCI_APP_TOKEN'] ?? null;
        $appSecret = $_SERVER['FLOUCI_APP_SECRET'] ?? $_ENV['FLOUCI_APP_SECRET'] ?? null;

        if (!$appToken || !$appSecret) {
            throw new \RuntimeException('Les clés API Flouci ne sont pas configurées.');
        }

        // For debugging only
        if ($this->getParameter('kernel.debug')) {
            error_log('Using app_token: ' . substr($appToken, 0, 4) . '...');
            error_log('Using app_secret: ' . substr($appSecret, 0, 4) . '...');
        }

        $client = new Client();
        
        try {
            // Calculate the amount in millimes
            $amountInMillimes = (int)round($facture->getTotal() * 1000);
            
            // Construct full URLs for success and fail links
            $successLink = $this->generateUrl('app_facture_success', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $failLink = $this->generateUrl('app_facture_fail', ['id' => $facture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            
            // Create a tracking ID with appropriate length (10-50 chars as per error message)
            $trackingId = 'FACTURE_' . $facture->getId() . '_' . time();
            
            // Prepare the request payload exactly as in the documentation example
            $payload = [
                'app_token' => $appToken,
                'app_secret' => $appSecret,
                'amount' => (string)$amountInMillimes,
                'accept_card' => "true", 
                'session_timeout_secs' => 1200,
                'success_link' => $successLink,
                'fail_link' => $failLink,
                'developer_tracking_id' => $trackingId
            ];

            // Log the complete request for debugging
            if ($this->getParameter('kernel.debug')) {
                $logPayload = $payload;
                $logPayload['app_token'] = substr($payload['app_token'], 0, 4) . '...';
                $logPayload['app_secret'] = substr($payload['app_secret'], 0, 4) . '...';
                error_log('Flouci Request URL: https://developers.flouci.com/api/generate_payment');
                error_log('Success Link: ' . $successLink);
                error_log('Fail Link: ' . $failLink);
                error_log('Flouci Payload: ' . json_encode($logPayload));
            }

            // Make the API request - using curl directly to ensure format matches exactly
            $ch = curl_init('https://developers.flouci.com/api/generate_payment');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            $responseBody = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Debug the raw response
            if ($this->getParameter('kernel.debug')) {
                error_log('Flouci Response Status: ' . $statusCode);
                error_log('Flouci Response Body: ' . $responseBody);
                if ($curlError) {
                    error_log('Curl Error: ' . $curlError);
                }
            }
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($responseBody, true);
                
                if (isset($responseData['result']['link'])) {
                    // Store both the Flouci payment_id and our tracking ID if available
                    if (isset($responseData['result']['payment_id'])) {
                        // Convert the payment_id to integer since the entity expects an int
                        $paymentId = (int)$responseData['result']['payment_id'];
                        $facture->setPaiement_id($paymentId);
                        
                        // If you have a tracking ID field in your Facture entity, set it
                        if (method_exists($facture, 'setTrackingId')) {
                            $facture->setTrackingId($trackingId);
                        }
                        
                        // Using the injected entity manager instead of getDoctrine()
                        $entityManager->flush();
                    }
                    
                    // Redirect to Flouci payment page
                    return $this->redirect($responseData['result']['link']);
                } else {
                    throw new \RuntimeException('Format de réponse Flouci invalide: ' . $responseBody);
                }
            } else {
                throw new \RuntimeException('Erreur Flouci HTTP ' . $statusCode . ': ' . $responseBody);
            }

        } catch (\Exception $e) {
            // Log the full error for debugging
            if ($this->getParameter('kernel.debug')) {
                error_log('Flouci Error: ' . $e->getMessage());
                // Get trace for the first few levels
                $trace = $e->getTraceAsString();
                $traceLines = explode("\n", $trace);
                $shortTrace = array_slice($traceLines, 0, 5);
                error_log('Error trace (first 5 lines): ' . implode("\n", $shortTrace));
            }
            
            $errorMessage = $this->getParameter('kernel.debug') ? 
                $e->getMessage() : 
                'Une erreur est survenue lors de l\'initialisation du paiement.';
            
            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute('app_facture_new', ['id' => $facture->getId()]);
        }
    }

    #[Route('/{id}/success', name: 'app_facture_success', methods: ['GET'])]
    public function paymentSuccess(Facture $facture, EntityManagerInterface $entityManager): Response
    {
        // Create new payment record
        $paiement = new Paiement();
        $paiement->setDate(new \DateTime());
        $paiement->setMode('en ligne');
        $paiement->setMontant($facture->getTotal());
        $paiement->setReference('FLOUCI-' . time()); // Temporary reference

        // Persist the payment
        $entityManager->persist($paiement);
        $entityManager->flush(); // Flush to get the payment ID

        // Update the reference with the actual payment ID
        $paiement->setReference('FLOUCI-' . $paiement->getId());
        
        // Link the payment to the invoice
        $facture->setPaiement_id($paiement->getId());
        
        // Save all changes
        $entityManager->flush();

        $this->addFlash('success', 'Le paiement a été effectué avec succès !');
        return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
    }
    
    #[Route('/{id}/fail', name: 'app_facture_fail', methods: ['GET'])]
    public function paymentFail(Facture $facture): Response
    {
        $this->addFlash('error', 'Le paiement a échoué. Veuillez réessayer.');
        return $this->redirectToRoute('app_facture_show', ['id' => $facture->getId()]);
    }

    #[Route('/facture/{id}/pdf', name: 'app_facture_pdf')]
    public function generatePdf(Facture $facture): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('facture/pdf.html.twig', [
            'facture' => $facture,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture_' . $facture->getNum() . '.pdf"',
            ]
        );
    }
}
