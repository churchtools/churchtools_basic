<?php
class PDF extends FPDF {
  // Kopfzeile
  function Header() {
    // Logo
    // $this->Image(ASSETS.'/img/ct-icon_256.png',10,8,33);
    // Arial fett 15
    $this->SetFont('Arial', 'B', 9);
    // nach rechts gehen
    $this->Cell(12, 7, '', 0);
    // Titel
    $this->Cell(13, 8, t('salutation'), 0, 0, 'L');
    $this->Cell(48, 8, t('name'), 0, 0, 'L');
    $this->Cell(45, 8, t('address'), 0, 0, 'L');
    $this->Cell(20, 8, t('birth.'), 0, 0, 'L');
    $this->Cell(30, 8, t('contact.informations'), 0, 0, 'L');
    $fields = _home__memberlist_getSettingFields()->fields;
    if ($fields["memberlist_telefonhandy"]->getValue()) $this->Cell(30, 8, t('mobile'), 0, 0, 'L');
    // Zeilenumbruch
    $this->SetLineWidth(0.1);
    $this->SetDrawColor(200, 200, 200);
    $this->Line(8, $this->GetY(), 204, $this->GetY());
    $this->Ln(9);
    $this->Line(8, $this->GetY()- 1, 204, $this->GetY()- 1);
  }

  // Fusszeile
  function Footer() {
    // Position 1,5 cm von unten
    $this->SetY(-10);
    // Arial kursiv 8
    $this->SetFont('Arial', 'I', 8);
    // Seitenzahl
    $this->Cell(0, 5, t('page'). ' '. $this->PageNo(). '/{nb}', 0, 0, 'C');
  }

}
