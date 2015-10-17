<?php
/* @var $this AenderungsantraegeController */
/* @var $model Aenderungsantrag */

$this->breadcrumbs = array(
	Yii::t('app', 'Administration') => $this->createUrl('/admin/index'),
	$model->label(2)                => array('index'),
	Yii::t('app', 'Update'),
);

$ae_link = $this->createUrl("aenderungsantrag/anzeige", array("veranstaltung_id" => $model->antrag->veranstaltung->url_verzeichnis, "antrag_id" => $model->antrag->id, "aenderungsantrag_id" => $model->id));
$this->menu = array(
	array('label' => $model->label() . ' ' . Yii::t('app', 'View'), 'url'=> $ae_link, "icon" => "eye-open"),
	array('label' => $model->label() . ' ' . Yii::t('app', 'Delete'), 'url' => '#', 'linkOptions' => array('submit' => array('delete', 'id' => $model->id), 'confirm' => 'Are you sure you want to delete this item?'), "icon" => "remove"),
);
?>
<h1><?php echo Yii::t('app', 'Update') . ': ' . GxHtml::encode($model->label()) ?></h1>
<br>
<?php

	if ($model->status == IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT) {

		$form = $this->beginWidget('GxActiveForm');

		$new_rev = $model->naechsteAenderungsRevNr();
		$einst = $model->antrag->veranstaltung->getEinstellungen();
		if (!$einst->ae_nummerierung_global && !$einst->ae_nummerierung_nach_zeile) $new_rev_long = $new_rev . " zu " . $model->antrag->revision_name;
		else $new_rev_long = $new_rev;
		echo '<input type="hidden" name="' . AntiXSS::createToken("antrag_freischalten") . '" value="' . CHtml::encode($new_rev). '">';
		echo "<div style='text-align: center;'>";
		$this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit', 'type' => 'primary', 'icon'=>'ok white', 'label'=>'Freischalten als ' . $new_rev_long));
		echo "</div>";
		$this->endWidget();
		echo "<br>";
	}

	if (count($messages) > 0) echo "<strong>" . GxHtml::encode(implode("<br>", $messages)) . "</strong><br><br>";


	$this->renderPartial('_form', array(
	'model' => $model));
