<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ReCaptcha\ReCaptcha;
use App\Service\PdfGeneratorService;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/entreprise')]
final class EntrepriseController extends AbstractController
{
    private $recaptcha;
    private $pdfGenerator;

    public function __construct(ReCaptcha $recaptcha, PdfGeneratorService $pdfGenerator)
    {
        $this->recaptcha = $recaptcha;
        $this->pdfGenerator = $pdfGenerator;
    }

    #[Route(name: 'app_entreprise_index', methods: ['GET'])]
    public function index(EntrepriseRepository $entrepriseRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $entrepriseRepository->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // Items per page
        );

        return $this->render('entreprise/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_entreprise_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify reCAPTCHA
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $remoteIp = $request->getClientIp();

            $result = $this->recaptcha
                ->setExpectedHostname($request->getHost())
                ->verify($recaptchaResponse, $remoteIp);

            if (!$result->isSuccess()) {
                $this->addFlash('error', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
                return $this->redirectToRoute('app_entreprise_new');
            }

            $entityManager->persist($entreprise);
            $entityManager->flush();

            return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('entreprise/new.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form,
            'google_recaptcha_site_key' => $this->getParameter('google_recaptcha_site_key'),
        ]);
    }

    #[Route('/{id}', name: 'app_entreprise_show', methods: ['GET'])]
    public function show(Entreprise $entreprise): Response
    {
        return $this->render('entreprise/show.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_entreprise_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entreprise $entreprise, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('entreprise/edit.html.twig', [
            'entreprise' => $entreprise,
            'form' => $form,
            'google_recaptcha_site_key' => $this->getParameter('google_recaptcha_site_key'),
        ]);
    }

    #[Route('/{id}', name: 'app_entreprise_delete', methods: ['POST'])]
    public function delete(Request $request, Entreprise $entreprise, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entreprise->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($entreprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_entreprise_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_entreprise_pdf', methods: ['GET'])]
    public function generatePdf(Entreprise $entreprise): Response
    {
        $result = $this->pdfGenerator->generatePdf($entreprise);
        
        if (!$result['success']) {
            $this->addFlash('error', 'Échec de la génération du PDF: ' . $result['error']);
            return $this->redirectToRoute('app_entreprise_index');
        }

        return $this->redirect($result['url']);
    }
}
