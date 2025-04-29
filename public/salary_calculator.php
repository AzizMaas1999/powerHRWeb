<?php

// Simple Tunisian salary calculator
function calculateSalaryDetails($childrenCount, $netSalary) {
    // Convert to proper numeric values
    $netSalaryValue = floatval(str_replace(',', '.', $netSalary));
    $childrenCountValue = intval($childrenCount);
    
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

// Process form submission
$childrenCount = 0;
$netSalary = 0;
$result = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $childrenCount = isset($_POST["childrenCount"]) ? $_POST["childrenCount"] : 0;
    $netSalary = isset($_POST["netSalary"]) ? $_POST["netSalary"] : 0;
    
    $result = calculateSalaryDetails($childrenCount, $netSalary);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Calculateur de Salaire Tunisien</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1e1e2f;
            color: #ffffff;
        }
        .card {
            background-color: #27293d;
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            margin-top: 50px;
        }
        .card-header {
            background-color: #1d8cf8;
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
        }
        input.form-control, select.form-control {
            background-color: #2b3553;
            border: 1px solid #2b3553;
            color: white;
        }
        input.form-control:focus, select.form-control:focus {
            background-color: #2b3553;
            border: 1px solid #1d8cf8;
            color: white;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #1d8cf8;
            border-color: #1d8cf8;
        }
        .table {
            color: white;
        }
        .table-dark {
            background-color: #27293d;
        }
        .bg-primary {
            background-color: #1d8cf8 !important;
        }
        .bg-success {
            background-color: #00bf9a !important;
        }
        .text-muted {
            color: #9a9a9a !important;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="mb-0">Calculateur de Salaire Tunisien</h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-4">
                                <label for="childrenCount" class="form-label">Nombre d'enfants</label>
                                <input type="number" class="form-control" id="childrenCount" name="childrenCount" 
                                       value="<?php echo htmlspecialchars($childrenCount); ?>" min="0" required>
                                <div class="form-text text-muted">Indiquez le nombre d'enfants pour le calcul des allocations</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="netSalary" class="form-label">Salaire Net (TND)</label>
                                <input type="number" step="0.001" class="form-control" id="netSalary" name="netSalary" 
                                       value="<?php echo htmlspecialchars($netSalary); ?>" min="0" required>
                                <div class="form-text text-muted">Entrez votre salaire net mensuel en TND</div>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" checked disabled id="cadreStatus">
                                <label class="form-check-label" for="cadreStatus">
                                    Statut Cadre
                                </label>
                                <div class="form-text text-muted">Le calcul est effectué avec le statut cadre</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary px-4">Calculer</button>
                        </form>
                        
                        <div class="mt-5">
                            <h4 class="border-bottom pb-2 mb-4">Résultats du calcul</h4>
                            
                            <div class="table-responsive">
                                <table class="table table-dark table-bordered">
                                    <tbody>
                                        <tr class="bg-primary">
                                            <th scope="row">Salaire Brut</th>
                                            <td class="fw-bold"><?php echo isset($result) ? $result['salaireBrut'] : 'Complétez le formulaire'; ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Retenues Totales</th>
                                            <td><?php echo isset($result) ? $result['retenu'] : 'Complétez le formulaire'; ?></td>
                                        </tr>
                                        <tr class="bg-success">
                                            <th scope="row">Salaire Net</th>
                                            <td class="fw-bold"><?php echo $netSalary ? $netSalary . ' TND' : 'Complétez le formulaire'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Ces calculs sont basés sur une simulation des paramètres fiscaux tunisiens. Les résultats sont donnés à titre indicatif.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Optional JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>