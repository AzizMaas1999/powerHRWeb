<?php

namespace App\Controller;

use App\Service\PayCalculatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SalaryCalculatorController extends AbstractController
{
    #[Route('/salary-calculator', name: 'app_salary_calculator')]
    public function index(Request $request, PayCalculatorService $calculatorService): Response
    {
        $result = null;
        $childrenCount = null;
        $netSalary = null;
        
        if ($request->isMethod('POST')) {
            // Get the values from the form
            $childrenCount = $request->request->get('childrenCount', 0);
            $netSalary = $request->request->get('netSalary', 0);
            
            // Directly calculate without complex validation
            $result = $calculatorService->calculateSalaryDetails($childrenCount, $netSalary);
        }
        
        return $this->render('salary_calculator/index.html.twig', [
            'result' => $result,
            'childrenCount' => $childrenCount,
            'netSalary' => $netSalary
        ]);
    }
}