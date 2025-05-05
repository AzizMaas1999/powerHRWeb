<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use App\Repository\FactureRepository;
use App\Service\CurrencyConverterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_FACTURATION')]
#[Route('/paiement')]
final class PaiementController extends AbstractController
{
    private $currencyConverter;

    public function __construct(CurrencyConverterService $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    #[Route(name: 'app_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository): Response
    {
        return $this->render('paiement/index.html.twig', [
            'paiements' => $paiementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FactureRepository $factureRepository): Response
    {
        $paiement = new Paiement();
        $facturesSansPaiement = $factureRepository->findBy(['paiement_id' => null]);

        $form = $this->createForm(PaiementType::class, $paiement, [
            'factures' => $facturesSansPaiement,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedFactures = $form->get('factures')->getData();

            $totalFactures = 0;
            foreach ($selectedFactures as $facture) {
                $totalFactures += $facture->getTotal();
            }

            if ($paiement->getMontant() != $totalFactures) {
                $this->addFlash('error', 'Le montant du paiement doit correspondre au total des factures sélectionnées.');
                return $this->render('paiement/new.html.twig', [
                    'paiement' => $paiement,
                    'form' => $form,
                    'currencies' => $this->currencyConverter->getSupportedCurrencies()
                ]);
            }

            // Si une devise différente de TND est sélectionnée, convertir le montant
            $devise = $request->request->get('devise', 'TND');
            if ($devise !== 'TND') {
                $montantOriginal = $paiement->getMontant();
                $montantConverti = $this->currencyConverter->convert($montantOriginal, $devise, 'TND');
                
                if ($montantConverti === null) {
                    $this->addFlash('error', 'Erreur lors de la conversion du montant. Veuillez réessayer.');
                    return $this->render('paiement/new.html.twig', [
                        'paiement' => $paiement,
                        'form' => $form,
                        'currencies' => $this->currencyConverter->getSupportedCurrencies()
                    ]);
                }
                
                $paiement->setMontant($montantConverti);
            }

            $entityManager->persist($paiement);
            $entityManager->flush();

            foreach ($selectedFactures as $facture) {
                $facture->setPaiement_id($paiement->getId());
                $entityManager->persist($facture);
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Paiement effectué : %.2f TND',
                $paiement->getMontant()
            ));

            return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
            'currencies' => $this->currencyConverter->getSupportedCurrencies()
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
            'currencies' => $this->currencyConverter->getSupportedCurrencies()
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_delete', methods: ['POST'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $paiement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paiement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/recherche', name: 'app_paiement_search', methods: ['GET'])]
    public function search(Request $request, PaiementRepository $paiementRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        $paiements = $paiementRepository->createQueryBuilder('p')
            ->where('LOWER(p.reference) LIKE :query')
            ->orWhere('LOWER(p.mode) LIKE :query')
            ->orWhere('CAST(p.montant AS string) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->getQuery()
            ->getResult();

        $result = [];

        foreach ($paiements as $paiement) {
            $result[] = [
                'id' => $paiement->getId(),
                'date' => $paiement->getDate()?->format('d/m/Y'),
                'mode' => ucfirst($paiement->getMode()),
                'reference' => $paiement->getReference(),
                'montant' => $paiement->getMontant(),
            ];
        }

        return new JsonResponse($result);
    }
}
