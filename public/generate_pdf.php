<?php
require('fpdf/fpdf.php');

if (!isset($_POST['distance']) || !isset($_POST['duration']) || !isset($_POST['routeInstructions'])) {
    die("Données manquantes pour générer le PDF.");
}

$distance = floatval($_POST['distance']); 
$duration = intval($_POST['duration']);   
$instructions = json_decode($_POST['routeInstructions'], true); 

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Compte Rendu d\'Itinéraire', 0, 1, 'C');
        $this->Ln(10); 
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function AddItineraryInfo($distance, $duration, $instructions)
    {
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 10, "Distance totale : " . number_format($distance / 1000, 2) . " km");
        $this->MultiCell(0, 10, "Durée totale : " . round($duration / 60) . " minutes");
        $this->Ln(10); 

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Détails des Instructions :', 0, 1);
        $this->SetFont('Arial', '', 12);

        foreach ($instructions as $instruction) {
            $this->MultiCell(0, 10, "- " . $instruction);
        }
    }
}

$pdf = new PDF();
$pdf->AddPage();

$pdf->AddItineraryInfo($distance, $duration, $instructions);

$pdf->Output('D', 'Compte_Rendu_Itineraire.pdf');
?>
