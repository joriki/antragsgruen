<?php

/**
 * @var AenderungsantragController $this
 * @var Aenderungsantrag $aenderungsantrag
 * @var Sprache $sprache
 */

$antrag = $aenderungsantrag->antrag;

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	"Antrag"                                         => $this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)),
	"Änderungsantrag"                                => $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id)),
	"Bearbeiten",
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");

$bearbeiten_link = CHtml::encode($this->createUrl("aenderungsantrag/aendern", array("antrag_id" => $aenderungsantrag->antrag->id, "aenderungsantrag_id" => $aenderungsantrag->id)));
?>

<h1>Änderungsantrag verwalten</h1>

<h3>Nachträglich Bearbeiten</h3>

<a class="btn btn-small btn-info" href="<?= $bearbeiten_link ?>"><i class="icon-wrench icon-white"></i> Ändern</a>

<br><br>

<h3>Zurückziehen</h3>

<?php
$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'   => 'horizontalForm',
	'type' => 'horizontal',
));
?>
<input type="hidden" name="<?= AntiXSS::createToken("ae_del") ?>" value="1">
<?php

$this->widget('bootstrap.widgets.TbButton', array('buttonType' => 'submit', 'type' => 'danger', 'icon' => 'trash white', 'label' => 'Zurückziehen'));

$this->endWidget();

?>
<script>
	$(function () {
		$(".btn-danger").parents("form").submit(function (ev) {
			if (!confirm("Wirklich zurückziehen?")) ev.preventDefault();
		});
	});
</script>
