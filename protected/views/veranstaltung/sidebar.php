<?php

/**
 * @var VeranstaltungenController $this
 * @var Veranstaltung $veranstaltung
 * @var string|null $einleitungstext
 * @var array $antraege
 * @var null|Person $ich
 * @var array|AntragKommentar[] $neueste_kommentare
 * @var array|Antrag[] $neueste_antraege
 * @var array|Aenderungsantrag[] $neueste_aenderungsantraege
 * @var Sprache $sprache
 */


$html = "<form class='form-search hidden-phone' action='" . $this->createUrl("veranstaltung/suche") . "' method='GET'><input type='hidden' name='id' value='" . $veranstaltung->id . "'><div class='nav-list'><div class='nav-header'>" . $sprache->get("Suche") . "</div>";
$html .= "<div style='text-align: center;'>  <div class='input-append'><input class='search-query' type='search' name='suchbegriff' value='' autofocus placeholder='Suchbegriff...'><button type='submit' class='btn'><i style='height: 17px;' class='icon-search'></i></button></div></div>";
$html .= "</div></form>";
$this->menus_html[] = $html;

$antrag_stellen_link = "";
if ($veranstaltung->getPolicyAntraege()->checkCurUserHeuristically()) {
	$antrag_stellen_link = $this->createUrl("antrag/neu");
} elseif ($veranstaltung->getPolicyAntraege()->checkHeuristicallyAssumeLoggedIn()) {
	$antrag_stellen_link = $this->createUrl("veranstaltung/login", array("back" => $this->createUrl("antrag/neu")));
}
$antrag_link = veranstaltungsspezifisch_antrag_einreichen_str($this->veranstaltung, $antrag_stellen_link);
if ($antrag_link) {
	$this->menus_html_presidebar = $antrag_link;
} else {
	$this->menus_html[] = '<a class="neuer-antrag" href="' . CHtml::encode($antrag_stellen_link) . '" title="' . CHtml::encode($sprache->get("Neuen Antrag stellen")) . '"></a>';
}

if (!in_array($veranstaltung->policy_antraege, array("Admins"))) {
	$html = "<div><ul class='nav nav-list neue-antraege'><li class='nav-header'>" . $sprache->get("Neue Anträge") . "</li>";
	if (count($neueste_antraege) == 0) $html .= "<li><i>keine</i></li>";
	else foreach ($neueste_antraege as $ant) {
		$html .= "<li";
		switch ($ant->typ) {
			case Antrag::$TYP_ANTRAG:
				$html .= " class='antrag'";
				break;
			case Antrag::$TYP_RESOLUTION:
				$html .= " class='resolution'";
				break;
			default:
				$html .= " class='resolution'";
		}
		$html .= ">" . CHtml::link($ant["name"], $this->createUrl("antrag/anzeige", array("antrag_id" => $ant["id"]))) . "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;
}

if (!in_array($veranstaltung->policy_aenderungsantraege, array("Admins"))) {
	$html = "<div><ul class='nav nav-list neue-aenderungsantraege'><li class='nav-header'>" . $sprache->get("Neue Änderungsanträge") . "</li>";
	if (count($neueste_aenderungsantraege) == 0) $html .= "<li><i>keine</i></li>";
	else foreach ($neueste_aenderungsantraege as $ant) {
		$zu_str = ($veranstaltung->getEinstellungen()->revision_name_verstecken ? CHtml::encode($ant->antrag->name) : CHtml::encode($ant->antrag->revision_name));
		$html .= "<li class='aeantrag'>" . CHtml::link("<strong>" . CHtml::encode($ant["revision_name"]) . "</strong> zu " . $zu_str, $this->createUrl("aenderungsantrag/anzeige", array("aenderungsantrag_id" => $ant->id, "antrag_id" => $ant->antrag->id))) . "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;
}

if ($veranstaltung->typ == Veranstaltung::$TYP_PROGRAMM) {
	if ($veranstaltung->getPolicyAntraege()->checkCurUserHeuristically()) {
		$this->menus_html[] = '<a class="neuer-antrag" href="' . CHtml::encode($this->createUrl("antrag/neu")) . '"></a>';
	}
}

if (!in_array($veranstaltung->policy_kommentare, array(0, 4))) {
	$html = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Neue Kommentare</li>";
	if (count($neueste_kommentare) == 0) $html .= "<li><i>keine</i></li>";
	else foreach ($neueste_kommentare as $komm) {
		$html .= "<li class='komm'>";
		$html .= "<strong>" . CHtml::encode($komm->verfasserIn->name) . "</strong>, " . HtmlBBcodeUtils::formatMysqlDateTime($komm->datum);
		$html .= "<div>Zu " . CHtml::link(CHtml::encode($komm->antrag->name), $this->createUrl("antrag/anzeige", array("antrag_id" => $komm->antrag_id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id))) . "</div>";
		$html .= "</li>\n";
	}
	$html .= "</ul></div>";
	$this->menus_html[] = $html;
}


$html = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>Benachrichtigungen</li>";
$html .= "<li class='benachrichtigung'>" . CHtml::link($sprache->get("E-Mail-Benachrichtigung bei neuen Anträgen"), $this->createUrl("veranstaltung/benachrichtigungen")) . "</li>";
$html .= "</ul></div>";

$this->menus_html[] = $html;


$html = "";

if ($veranstaltung->getEinstellungen()->feeds_anzeigen) {
	$feeds = 0;
	if (!in_array($veranstaltung->policy_antraege, array("Admins"))) {
		$html .= "<li class='feed'>" . CHtml::link($sprache->get("Anträge"), $this->createUrl("veranstaltung/feedAntraege")) . "</li>";
		$feeds++;
	}
	if (!in_array($veranstaltung->policy_aenderungsantraege, array("Admins"))) {
		$html .= "<li class='feed'>" . CHtml::link($sprache->get("Änderungsanträge"), $this->createUrl("veranstaltung/feedAenderungsantraege")) . "</li>";
		$feeds++;
	}
	if (!in_array($veranstaltung->policy_kommentare, array(0, 4))) {
		$html .= "<li class='feed'>" . CHtml::link($sprache->get("Kommentare"), $this->createUrl("veranstaltung/feedKommentare")) . "</li>";
		$feeds++;
	}
	if ($feeds > 1) $html .= "<li class='feed'>" . CHtml::link($sprache->get("Alles"), $this->createUrl("veranstaltung/feedAlles")) . "</li>";

	$feeds_str = ($feeds == 1 ? "Feed" : "Feeds");
	$html      = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>" . $feeds_str . "</li>" . $html . "</ul></div>";

	$this->menus_html[] = $html;
}

if ($veranstaltung->getEinstellungen()->kann_pdf) {
	$name = ($veranstaltung->url_verzeichnis == "ltwby13-programm" ? "Das gesamte Programm als PDF" : $sprache->get("Alle PDFs zusammen"));
	$html = "<div><ul class='nav nav-list neue-kommentare'><li class='nav-header'>PDFs</li>";
	$html .= "<li class='pdf'>" . CHtml::link($name, $this->createUrl("veranstaltung/pdfs")) . "</li>";
	if (!in_array($veranstaltung->policy_aenderungsantraege, array("Admins")) || $veranstaltung->url_verzeichnis == "ltwby13-programm") $html .= "<li class='pdf'>" . CHtml::link("Alle " . $sprache->get("Änderungsanträge") . " gesammelt", $this->createUrl("veranstaltung/aenderungsantragsPdfs")) . "</li>";
	$html .= "</ul></div>";
	$this->menus_html[] = $html;
}

if (veranstaltungsspezifisch_antragsgruen_in_sidebar($veranstaltung)) {
	$html = "</div><div class='antragsgruen_werbung well'><div class='nav-list'>";
	$html .= "<div class='nav-header'>Dein Antragsgrün</div>";
	$html .= "<div class='content'>Du willst Antragsgrün selbst für deine(n) KV / LV / GJ / BAG / LAG einsetzen?";
	$html .= "<div style='text-align: center;'><a href='" . CHtml::encode($this->createUrl("infos/selbstEinsetzen")) . "' class='btn btn-primary' style='margin-top: 15px;' title='Das Antragstool selbst einsetzen'><span class='icon-chevron-right'></span> Infos</a></div>";
	$html .= "</div>";
	$html .= "</div>";
	$this->menus_html[] = $html;
}
