<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var bool $js_protection
 * @var array $hiddens
 * @var bool $edit_link
 * @var array $kommentare_offen
 * @var string $komm_del_link
 * @var string|null $admin_edit
 * @var Person $kommentar_person
 * @var bool $support_form
 * @var string $support_status
 * @var Sprache $sprache
 */

$this->breadcrumbs = array(
	CHtml::encode($aenderungsantrag->antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get("Antrag")                                            => $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id)),
	$sprache->get("Änderungsantrag")
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
$this->pageTitle = $aenderungsantrag->revision_name . " zu: " . $aenderungsantrag->antrag->nameMitRev();

/** @var CWebApplication $app */
$app = Yii::app();
$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/socialshareprivacy/jquery.socialshareprivacy.js');

$html = '<ul class="funktionen">';
//$html .= '<li class="unterstuetzen"><a href="#">Änderungsantrag unterstützen</a></li>';
if ($admin_edit) $html .= '<li class="admin_edit">' . CHtml::link("Admin: bearbeiten", $admin_edit) . '</li>';
if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->kann_pdf) {
	$html .= '<li class="download">' . CHtml::link($sprache->get("PDF-Version herunterladen"), $this->createUrl("aenderungsantrag/pdf", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
	$html .= '<li class="download">' . CHtml::link($sprache->get("PDF: Kompakt"), $this->createUrl("aenderungsantrag/pdfDiff", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
}
if ($edit_link) $html .= '<li class="edit">' . CHtml::link("Änderungsantrag bearbeiten", $this->createUrl("aenderungsantrag/bearbeiten", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id))) . '</li>';
$html .= '<li class="zurueck">' . CHtml::link("Zurück zum Antrag", $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id))) . '</li>
</ul>';

$this->menus_html[] = $html;

$rows = 10;
$antragstellerInnen = array();
foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) if ($unt->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN) {
	$antragstellerInnen[] = $unt->getNameMitBeschlussdatum(true);
}


if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->ae_nummerierung_global) {
	$ae_kuerzel = $aenderungsantrag->revision_name;
} else {
	$arev = $aenderungsantrag->antrag->revision_name;
	if (stripos($aenderungsantrag->revision_name, $arev) === false) {
		$ae_kuerzel = $aenderungsantrag->revision_name . " zu " . $arev;
	} else {
		$ae_kuerzel = $aenderungsantrag->revision_name;
	}
}
?>
	<h1><?php echo CHtml::encode($sprache->get("Änderungsantrag") . " " . $ae_kuerzel); ?></h1>

	<div class="antragsdaten" style="min-height: 114px;">
		<div id="socialshareprivacy"></div>
		<script>
			$(function ($) {
				$('#socialshareprivacy').socialSharePrivacy({
					css_path: "/socialshareprivacy/socialshareprivacy.css"
				});
			});
		</script>
		<div class="content">

			<table class="antragsdaten">
				<tr>
					<th>Veranstaltung:</th>
					<td><?php
						echo CHtml::link($aenderungsantrag->antrag->veranstaltung->name, $this->createUrl("veranstaltung/index"));
						?></td>
				</tr>
				<tr>
					<th>Ursprungsantrag:</th>
					<td><?php
						echo CHtml::link($aenderungsantrag->antrag->name, $this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id)));
						?></td>
				</tr>
				<tr>
					<th><?php echo(count($antragstellerInnen) > 1 ? "AntragsstellerInnen" : "AntragsstellerIn"); ?>:</th>
					<td><?php
						$x = array();
						foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) if ($unt->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
							$name= $unt->getNameMitBeschlussdatum(true);
							if ($unt->person->istWurzelwerklerIn()) {
								$name .= ' (<a href="https://wurzelwerk.gruene.de/web/' . CHtml::encode($unt->person->getWurzelwerkName()) . '">Wurzelwerk-Profil</a>)';
							}
							if ($aenderungsantrag->antrag->veranstaltung->isAdminCurUser() && ($unt->person->email != "" || $unt->person->telefon != "")) {
								$name .= " <small>(Kontaktdaten, nur als Admin sichtbar: ";
								if ($unt->person->email != "") $name .=  "E-Mail: " . CHtml::encode($unt->person->email);
								if ($unt->person->email != "" && $unt->person->telefon != "") $name .=  ", ";
								if ($unt->person->telefon != "") $name .=  "Telefon: " . CHtml::encode($unt->person->telefon);
								$name .=  ")</small>";
							}
							$x[] = $name;
						}
						echo implode(", ", $x);
						?></td>
				</tr>
				<tr>
					<th>Status:</th>
					<td><?php
						echo CHtml::encode(IAntrag::$STATI[$aenderungsantrag->status]);
						if (trim($aenderungsantrag->status_string) != "") echo " <small>(" . CHtml::encode($aenderungsantrag->status_string) . ")</string>";
						?></td>
				</tr>
				<?php if ($aenderungsantrag->datum_beschluss != "") { ?>
					<tr>
						<th>Beschlossen am:</th>
						<td><?php
							echo HtmlBBcodeUtils::formatMysqlDate($aenderungsantrag->datum_beschluss);
							?></td>
					</tr>
				<?php } ?>
				<tr>
					<th>Eingereicht:</th>
					<td><?php
						echo HtmlBBcodeUtils::formatMysqlDateTime($aenderungsantrag->datum_einreichung);
						?></td>
				</tr>
			</table>
			<?php
			$this->widget('bootstrap.widgets.TbAlert', array(
				'block' => true,
				'fade'  => true,
			));
			?>
			<!--
			<div class="hidden-desktop">
				<div style="text-align: center; padding-top: 25px;">
					<button class="btn" type="button" style="color: black;"><i class="icon-pdf"></i> PDF-Version</button>
				</div>
			</div>
			-->
		</div>
	</div>
	<br>


	<div
		class="antrags_text_holder<?php if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->zeilenlaenge > 80) echo " kleine_schrift"; ?>">
		<h3>Änderungsantragstext</h3>

		<?php
		$dummy_komm = new AenderungsantragKommentar();

		$zeit_von = time();
		$absae = $aenderungsantrag->getAntragstextParagraphs_diff();
		$diff = time() - $zeit_von;

		foreach ($absae as $i => $abs) if ($abs !== null) {
			/** @var AenderungsantragAbsatz $abs */

			$kommoffenclass = (!in_array($i, $kommentare_offen) ? "kommentare_closed_absatz" : "");

			?>
			<div class='row-fluid row-absatz <?php echo $kommoffenclass; ?>' data-absatznr='<?php echo $i; ?>'>
				<ul class="lesezeichen">
					<?php if (count($abs->kommentare) > 0 || $aenderungsantrag->antrag->veranstaltung->darfEroeffnenKommentar()) { ?>
						<li class='kommentare'>
							<a href='#' class='shower'><?php echo count($abs->kommentare); ?></a>
							<a href='#' class='hider'><?php echo count($abs->kommentare); ?></a>
						</li>
					<?php } ?>
				</ul>

				<div class="absatz_text orig antragabsatz_holder antrags_text_holder_nummern">
					<?php
					echo $abs->getDiffHTML();
					?>
				</div>
				<?php

				/** @var AenderungsantragKommentar $komm */
				foreach ($abs->kommentare as $komm) {
					$komm_link = $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id, "kommentar_id" => $komm->id, "#" => "komm" . $komm->id));
					?>
					<div class="kommentarform" id="komm<?= $komm->id ?>">
						<div class="datum"><?php echo HtmlBBcodeUtils::formatMysqlDateTime($komm->datum) ?></div>
						<h3>Kommentar von <?php echo
							CHtml::encode($komm->verfasserIn->getNameMitOrga());
							if ($komm->status == IKommentar::$STATUS_NICHT_FREI) echo " <em>(noch nicht freigeschaltet)</em>";
							?></h3>
						<?php
						echo nl2br(CHtml::encode($komm->text));
						if (!is_null($komm_del_link) && $komm->kannLoeschen(Yii::app()->user)) echo "<div class='del_link'><a href='" . CHtml::encode(str_replace(rawurlencode("#komm_id#"), $komm->id, $komm_del_link)) . "'>x</a></div>";
						if ($komm->status == IKommentar::$STATUS_NICHT_FREI && $aenderungsantrag->antrag->veranstaltung->isAdminCurUser()) {
							$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
								'type'        => 'inline',
								'htmlOptions' => array('class' => ''),
								'action'      => $komm_link,
							));
							echo '<div style="display: inline-block; width: 49%; text-align: center;">';
							$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Freischalten', 'icon' => 'icon-thumbs-up', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_freischalten'))));
							echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';
							$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'label' => 'Löschen', 'icon' => 'icon-thumbs-down', 'htmlOptions' => array('name' => AntiXSS::createToken('komm_nicht_freischalten'))));
							echo '</div>';
							$this->endWidget();
						}
						?>
						<div
							class="kommentarlink"><?php echo CHtml::link("Kommentar verlinken", $komm_link); ?></div>
					</div>
				<?php
				}

				if ($aenderungsantrag->antrag->veranstaltung->darfEroeffnenKommentar()) {
					/** @var TbActiveForm $form */
					$form = $this->beginWidget('CActiveForm', array(
						"htmlOptions" => array(
							"class" => "kommentarform",
						),
					));
					?>
					<fieldset>
						<legend>Kommentar schreiben</legend>

						<?php

						if ($js_protection) {
							?>
							<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder
								JavaScript aktiviert sein, oder du musst eingeloggt sein.
							</div>
						<?php
						}
						foreach ($hiddens as $name => $value) {
							echo '<input type="hidden" name="' . CHtml::encode($name) . '" value="' . CHtml::encode($value) . '">';
						}
						echo '<input type="hidden" name="absatz_nr" value="' . $abs->absatz_nr . '">';
						?>
						<div class="row">
							<?php echo $form->labelEx($kommentar_person, 'name'); ?>
							<?php echo $form->textField($kommentar_person, 'name') ?>
						</div>
						<div class="row">
							<?php echo $form->labelEx($kommentar_person, 'email'); ?>
							<?php echo $form->emailField($kommentar_person, 'email') ?>
						</div>
						<div class="row">
							<?php echo $form->labelEx($dummy_komm, 'text'); ?>
							<?php echo $form->textArea($dummy_komm, 'text') ?>
						</div>
					</fieldset>

					<div class="submitrow">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'primary', 'icon' => 'ok white', 'label' => 'Kommentar abschicken'));
						?>
					</div>
					<?php
					$this->endWidget();
				}
				?>

			</div>
		<?php
		}
		?>
	</div>

	<div class="begruendungs_text_holder">
		<h3>Begründung</h3>

		<div class="textholder consolidated content">
			<?php
			if ($aenderungsantrag->aenderung_begruendung_html) echo $aenderungsantrag->aenderung_begruendung;
			else echo HtmlBBcodeUtils::bbcode2html($aenderungsantrag->aenderung_begruendung);
			?>
		</div>

		<br><br>
	</div>

<?php
$zustimmung_von = $aenderungsantrag->getZustimmungen();
$ablehnung_von = $aenderungsantrag->getAblehnungen();
$unterstuetzerInnen = $aenderungsantrag->getUnterstuetzerInnen();
$eintraege = (count($unterstuetzerInnen) > 0 || count($zustimmung_von) > 0 || count($ablehnung_von) > 0);
$unterstuetzen_policy = $aenderungsantrag->antrag->veranstaltung->getPolicyUnterstuetzen();
$kann_unterstuetzen = $unterstuetzen_policy->checkCurUserHeuristically();
$kann_nicht_unterstuetzen_msg = $unterstuetzen_policy->getPermissionDeniedMsg();

if ($eintraege || $kann_unterstuetzen || $kann_nicht_unterstuetzen_msg != "") {
	?>

	<h2>UnterstützerInnen</h2>

	<div class="content">
		<?php
		$curr_user_id = (Yii::app()->user->isGuest ? 0 : Yii::app()->user->getState("person_id"));

		echo "<strong>UnterstützerInnen:</strong><br>";
		if (count($unterstuetzerInnen) > 1) {
			echo CHtml::openTag('ul');
			foreach ($unterstuetzerInnen as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->getNameMitOrga());
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
		} elseif (count($unterstuetzerInnen) > 0) {
			$p = $unterstuetzerInnen[0];
			if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
			echo CHtml::encode($p->getNameMitOrga()) . "<br>";
		} else {
			echo '<em>keine</em><br>';
		}
		echo "<br>";

		if (count($zustimmung_von) > 0) {
			echo "<strong>Zustimmung von:</strong><br>";
			echo CHtml::openTag('ul');
			foreach ($zustimmung_von as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->getNameMitOrga());
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
			echo "<br>";
		}

		if (count($ablehnung_von) > 0) {
			echo "<strong>Abgelehnt von:</strong><br>";
			echo CHtml::openTag('ul');
			foreach ($ablehnung_von as $p) {
				echo CHtml::openTag('li');
				if ($p->id == $curr_user_id) echo '<span class="label label-info">Du!</span> ';
				echo CHtml::encode($p->getNameMitOrga());
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');
			echo "<br>";
		}
		?>
	</div>

	<?php
	if ($kann_unterstuetzen) {
		$form = $this->beginWidget('bootstrap.widgets.TbActiveForm');
		echo "<div style='text-align: center; margin-bottom: 20px;'>";
		switch ($support_status) {
			case IUnterstuetzerInnen::$ROLLE_INITIATORIN:
				break;
			case IUnterstuetzerInnen::$ROLLE_MAG:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'label' => 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions' => array('name' => AntiXSS::createToken('dochnicht'))));
				break;
			case IUnterstuetzerInnen::$ROLLE_MAG_NICHT:
				$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'label' => 'Zurückziehen', 'icon' => 'icon-remove', 'htmlOptions' => array('name' => AntiXSS::createToken('dochnicht'))));
				break;
			default:
				?>
					<div style="display: inline-block; width: 49%; text-align: center;">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'success', 'label' => 'Zustimmen', 'icon' => 'icon-thumbs-up', 'htmlOptions' => array('name' => AntiXSS::createToken('mag'))));
						?>
					</div>
					<div style="display: inline-block; width: 49%; text-align: center;">
						<?php
						$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'label' => 'Ablehnen', 'icon' => 'icon-thumbs-down', 'htmlOptions' => array('name' => AntiXSS::createToken('magnicht'))));
						?>
					</div>
				<?php
		}
		echo "</div>";
		$this->endWidget();
	} else {
		/*
		Yii::app()->user->setFlash('warning', 'Um diesen Änderungsantrag unterstützen oder ablehnen zu können, musst du ' . CHtml::link("dich einzuloggen", $this->createUrl("veranstaltung/login")) . '.');
		$this->widget('bootstrap.widgets.TbAlert', array(
			'block'=> true,
			'fade' => true,
		));
		*/
		if ($kann_nicht_unterstuetzen_msg != "") {
			Yii::app()->user->setFlash('warning', $kann_nicht_unterstuetzen_msg);
			$this->widget('bootstrap.widgets.TbAlert', array(
				'block' => true,
				'fade'  => true,
			));
		}

	} ?>
<?php
}