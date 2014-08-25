<?php

/**
 * @var AntragController $this
 * @var Antrag $antrag
 * @var Sprache $sprache
 */

/** @var array|Person[] $antragstellerInnen */
$antragstellerInnen = array();
/** @var array|Person[] $unterstuetzerInnen */
$unterstuetzerInnen = array();

if (count($antrag->antragUnterstuetzerInnen) > 0) foreach ($antrag->antragUnterstuetzerInnen as $relatedModel) {
	if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $antragstellerInnen[] = $relatedModel->person;
	if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $relatedModel->person;
}

$this->breadcrumbs = array(
	CHtml::encode($antrag->veranstaltung->name_kurz) => $this->createUrl("veranstaltung/index"),
	$sprache->get('Neuer Antrag'),
	' / Bestätigen'
);
$this->breadcrumbs_topname = $sprache->get("breadcrumb_top");
?>

<h1><?php echo CHtml::encode($antrag->name); ?></h1>

<div class="antrags_text_holder">
    <h3>Antragstext</h3>

	<?php

	Yii::app()->user->setFlash("info", $antrag->veranstaltung->getStandardtext("antrag_confirm")->getHTMLText());
	$this->widget('bootstrap.widgets.TbAlert');

	?>

    <div class="textholder consolidated">
		<?php
		$absae = $antrag->getParagraphs();
		foreach ($absae as $i=> $abs) {
			/** @var AntragAbsatz $abs */
			echo "<div class='absatz_text orig antragabsatz_holder antrags_text_holder_nummern'>";
			echo $abs->str_html;
			echo "</div>";
		}
		?>
    </div>
</div>

<div class="begruendungs_text_holder">
    <h3>Begründung</h3>

    <div class="textholder consolidated content">
		<?php echo HtmlBBcodeUtils::bbcode2html($antrag->begruendung) ?>
    </div>
</div>

<div class="antrags_text_holder">
    <h3>UnterstützerInnen</h3>

    <div class="content">
		<?php
		if (count($unterstuetzerInnen) > 0) {
			echo CHtml::openTag('ul');
			foreach ($unterstuetzerInnen as $p) {
				echo CHtml::openTag('li');
				echo CHtml::encode($p->getNameMitOrga());
				echo CHtml::closeTag('li');
			}
			echo CHtml::closeTag('ul');

		} else echo "<em>keine</em>";
		?>
    </div>
</div>
<?php
/** @var TbActiveForm $form */
$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'  => 'horizontalForm',
	'type'=> 'horizontal',
)); ?>

<input type="hidden" name="<?=AntiXSS::createToken("antragbestaetigen")?>" value="1">

<div class="form-actions">
    <div style="float: right;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submit', 'type'=> 'primary', 'icon'=> 'ok white', 'label'=> 'Antrag einreichen')); ?>
    </div>
    <div style="float: left;">
		<?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=> 'submitlink', 'url' => $this->createUrl("antrag/aendern", array("antrag_id" => $antrag->id)), 'icon'=> 'remove', 'label'=> 'Korrigieren')); ?>
    </div>
</div>

<?php $this->endWidget(); ?>