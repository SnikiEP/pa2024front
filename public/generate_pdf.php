<?php
require('fpdf/fpdf.php');

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('DejaVuSansCondensed', '', 14);
        $this->Cell(0, 10, 'Compte Rendu de l\'Itinéraire', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('DejaVuSansCondensed', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();

$pdf->AddFont('DejaVuSansCondensed', '', 'DejaVuSansCondensed'); 

$pdf->SetFont('DejaVuSansCondensed', 'B', 16);
$pdf->Cell(0, 10, "Détails de l'itinéraire", 0, 1, 'C');

$pdf->SetFont('DejaVuSansCondensed', '', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, "Distance totale : " . ($distance / 1000) . " km");
$pdf->MultiCell(0, 10, "Durée totale : " . round($duration / 60) . " minutes");

$pdf->Output('D', 'itineraire_complet.pdf');
?>
