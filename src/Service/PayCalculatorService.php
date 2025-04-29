<?php

namespace App\Service;

class PayCalculatorService
{
    /**
     * Calculate salary details using direct calculation
     * 
     * @param string $childrenCount Number of children
     * @param string $netSalary Net salary amount
     * 
     * @return array Array with 'retenu' and 'salaireBrut'
     */
    public function calculateSalaryDetails(string $childrenCount, string $netSalary): array
    {
        // Convert to float
        $netSalaryValue = floatval(str_replace(',', '.', $netSalary));
        $childrenCountValue = intval($childrenCount);
        
        // Simplified calculation based on Tunisian tax rules
        
        // Base tax rates
        $baseTaxRate = 0.18; // Base IRPP tax rate
        $socialSecurityRate = 0.092; // CNSS rate for employees
        
        // Child benefits (reduce tax rate slightly)
        $childBenefitRate = $childrenCountValue * 0.01; // 1% reduction per child
        
        // Calculate effective tax rate
        $effectiveTaxRate = max(0.15, $baseTaxRate + $socialSecurityRate - $childBenefitRate);
        
        // Calculate gross salary: Net = Gross - (Gross * taxRate)
        // Therefore: Gross = Net / (1 - taxRate)
        $grossSalary = $netSalaryValue / (1 - $effectiveTaxRate);
        $grossSalaryRounded = round($grossSalary, 3);
        
        // Calculate deductions
        $totalDeductions = $grossSalaryRounded - $netSalaryValue;
        $totalDeductionsRounded = round($totalDeductions, 3);
        
        // Format results
        $formattedGross = number_format($grossSalaryRounded, 3, '.', ' ') . ' TND';
        $formattedDeductions = number_format($totalDeductionsRounded, 3, '.', ' ') . ' TND';
        
        // Return the results
        return [
            'retenu' => $formattedDeductions,
            'salaireBrut' => $formattedGross
        ];
    }
}