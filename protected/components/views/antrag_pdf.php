<?php

/**
 * @var Antrag $antrag
 * @var TCPDF $pdf
 * @var Sprache $sprache
 * @var string $initiatorinnen
 * @var bool $header
 */

$absae = $antrag->getParagraphs();

// set font
$pdf->SetFont('dejavusans', '', 10);

// add a page
$pdf->AddPage();

$linenr = $antrag->getFirstLineNo();

list($logo, $initiatorinnen, $gegenstand, $ueberschrift, $revision_name, $default_font, $default_fontsize) = veranstaltungsspezifisch_antrag_pdf_header($antrag, $sprache, $initiatorinnen);

if (function_exists("normalizer_normalize")) {
    $ueberschrift = normalizer_normalize($ueberschrift);
}

if (count($antrag->tags) > 0 && $antrag->tags[0]->istTagesordnungspunkt()) {
    $tagesordnungspunktmodus = true;
    $ueberschrift            = $antrag->name;
} else {
    $tagesordnungspunktmodus = false;
}

if ($header) {

    if (file_exists($logo)) {
        $pdf->setJPEGQuality(100);
        $pdf->Image($logo, 22, 32, 47, 26);
    }

    if (!$antrag->veranstaltung->getEinstellungen()->revision_name_verstecken) {

        if ($revision_name == "") {
            $revision_name = "Entwurf";
            $pdf->SetFont("helvetica", "I", "25");
            $width = $pdf->GetStringWidth($revision_name, "helvetica", "I", "25") + 3.1;
        } else {
            $pdf->SetFont("helvetica", "B", "25");
            $width = $pdf->GetStringWidth($revision_name, "helvetica", "B", "25") + 3.1;
        }
        if ($width < 35) {
            $width = 35;
        }

        $pdf->SetXY(192 - $width, 37, true);
        $pdf->MultiCell($width, 21, $revision_name,
            array('LTRB' => array('width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))), "C",
            false, 1, "", "", true, 0, false, true, 21, // defaults
            "M"
        );
    }

    $str = Antrag::$TYPEN[$antrag->typ];
    $pdf->SetFont("helvetica", "B", "25");
    $width = $pdf->GetStringWidth($str);

    $pdf->SetXY((210 - $width) / 2, 60);
    $pdf->Write(20, $str);
    $pdf->SetLineStyle(array(
        "width" => 3,
        'color' => array(150, 150, 150),
    ));
    $pdf->Line((210 - $width) / 2, 78, (210 + $width) / 2, 78);

    $pdf->SetY(90);
    if ($antrag->veranstaltung->getEinstellungen()->antrag_einleitung != "") {
        $pdf->SetX(24);
        $pdf->SetFont("helvetica", "B", 12);
        $pdf->MultiCell(160, 13, $antrag->veranstaltung->getEinstellungen()->antrag_einleitung, 0, "C");
        $pdf->Ln(7);
    }

    $pdf->SetX(12);

    $pdf->SetFont("helvetica", "B", 12);
    $pdf->MultiCell(12, 0, "", 0, "L", false, 0);
    $pdf->MultiCell(50, 0, $sprache->get("AntragsstellerIn") . ":", 0, "L", false, 0);
    $pdf->SetFont("helvetica", "", 12);
    $pdf->MultiCell(120, 0, $initiatorinnen, 0, "L");

    $pdf->Ln(5);
    $pdf->SetX(12);

    $pdf->SetFont("helvetica", "B", 12);
    $pdf->MultiCell(12, 0, "", 0, "L", false, 0);
    if ($tagesordnungspunktmodus) {
        $pdf->MultiCell(50, 0, "Tagesordnungspunkt:", 0, "L", false, 0);
        $pdf->SetFont("helvetica", "B", 12);
        $pdf->MultiCell(100, 0, $antrag->tags[0]->name,
            array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))),
            "L"
        );
    } else {
        $pdf->MultiCell(50, 0, "Gegenstand:", 0, "L", false, 0);
        $pdf->SetFont("helvetica", "B", 12);
        $pdf->MultiCell(100, 0, $gegenstand,
            array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(150, 150, 150))),
            "L"
        );
    }

    $pdf->Ln(9);

}


$pdf->SetX(12);

$pdf->SetFont($default_font, "", $default_fontsize);

if ($antrag->veranstaltung->getEinstellungen()->titel_eigene_zeile) {
    $pdf->MultiCell(12, 0, $linenr - 1, 0, "L", false, 0);
}


$text2name = veranstaltungsspezifisch_text2_name($antrag->veranstaltung, $antrag->typ);
if ($text2name && trim($antrag->text2)) {
    $text = HtmlBBcodeUtils::bbcode2html($antrag->text2);
    if (function_exists("normalizer_normalize")) {
        $text = normalizer_normalize($text);
    }
    $html = '
	<h3>' . CHtml::encode($text2name) . '</h3>
	<div class="textholder consolidated">
		' . $text . '
	</div>
	<div></div>
    ';
    $pdf->SetFont("helvetica", "", 12);
    $pdf->writeHTML($html, true, false, true, false, '');
}


$pdf->SetFont("helvetica", "", 12);
$pdf->writeHTML("<h3>" . $ueberschrift . "</h3>");

$text_size = ($antrag->veranstaltung->getEinstellungen()->zeilenlaenge > 70 ? 10 : 11);
$pdf->SetFont("Courier", "", $text_size);
$pdf->Ln(7);


foreach ($absae as $i => $abs) {
    /** @var AntragAbsatz $abs */
    $text   = $abs->str_html;
    $zeilen = substr_count($text, "<span class='zeilennummer'>");

    $abstand_bevor = array();

    //preg_match_all("/<div[^>]*antragabsatz_holder[^>]*>(?:.*)<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);
    //foreach ($matches[1] as $line) if ($line > 1) $abstand_bevor[$line] = 25;

    preg_match_all("/<li><span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);
    foreach ($matches[1] as $line) {
        if (isset($abstand_bevor[$line])) {
            $abstand_bevor[$line] += 10;
        } else {
            $abstand_bevor[$line] = 10;
        }
    }

    preg_replace("/<li><span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", "<li style='margin-top: 10px;'>", $text);

    preg_match_all("/<div[^>]*antragabsatz_holder[^>]*>(?:.*)<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/siuU", $text, $matches);

    $text = preg_replace("/<span class=[\"']zeilennummer[\"']>([0-9]+)<\/span>/sii", "", $text);

    // Umlaute kommen manchmal mit alternativen UTF-8-Encodings rein, die von PDF nicht richtig dargestellt werden
    // Bsp.: 0x61CC88 für "ü"
    if (function_exists("normalizer_normalize")) {
        $text = normalizer_normalize($text);
    }

    $zeilennrs = array();
    for ($i = 0; $i < $zeilen; $i++) {
        $zeilennrs[] = $linenr++;
    }
    $text2 = implode("<br>", $zeilennrs);

    $y = $pdf->getY();
    $pdf->writeHTMLCell(12, '', 12, $y, $text2, 0, 0, 0, true, '', true);
    $pdf->writeHTMLCell(173, '', 24, '', $text, 0, 1, 0, true, '', true);

    $pdf->Ln(7);

}

if (trim($antrag->begruendung) != "") {
    if ($antrag->begruendung_html) {
        $begruendung = $antrag->begruendung;
    } else {
        $begruendung = HtmlBBcodeUtils::bbcode2html($antrag->begruendung);
    }
    if (function_exists("normalizer_normalize")) {
        $begruendung = normalizer_normalize($begruendung);
    }

    $bname = veranstaltungsspezifisch_begruendung_name($antrag->veranstaltung, $antrag->typ);
    if (!$bname) {
        $bname = "Begründung";
    }

    $html = '
	</div>
	<h3>' . $bname . '</h3>
	<div class="textholder consolidated">
		' . $begruendung . '
	</div>
</div>';
    $pdf->SetFont($default_font, "", $default_fontsize);
    $pdf->writeHTML($html, true, false, true, false, '');
}

$unterstuetzerInnen = $antrag->getUnterstuetzerInnen();
if (count($unterstuetzerInnen) > 0) {
    $html = '<br><h3>UnterstützerInnen</h3><ul>';
    foreach ($unterstuetzerInnen as $unt) {
        $html .= '<li>' . CHtml::encode($unt->name) . '</li>';
    }
    $html .= '</ul>';

    //$pdf->SetFont("helvetica", "", 12);
    $pdf->writeHTML($html, true, false, true, false, '');
}