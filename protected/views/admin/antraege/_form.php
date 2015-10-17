<div class="form content">


	<?php
	/**
	 * @var AntraegeController $this
	 * @var GxActiveForm $form
	 * @var Antrag $model
	 * @var AntraegeController $this
	 */

	/** @var CWebApplication $app */
	$app = Yii::app();
	$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/ckeditor/ckeditor.js');
	$app->getClientScript()->registerScriptFile($this->getAssetsBase() . '/js/bbcode/plugin.js');

	$form = $this->beginWidget('GxActiveForm', array(
		'id'                   => 'antrag-form',
		'enableAjaxValidation' => true,
	));
	?>

	<?php echo $form->errorSummary($model); ?>

	<div>
		<?php echo $form->labelEx($model, 'abgeleitet_von'); ?>
		<?php echo $form->dropDownList($model, 'abgeleitet_von',
			GxHtml::listDataEx(Antrag::model()->findAllAttributes(null, true)),
			array("empty" => "-")
		); ?>
		<?php echo $form->error($model, 'abgeleitet_von'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'typ'); ?>
		<?php echo $form->dropDownList($model, "typ", Antrag::$TYPEN); ?>
		<?php echo $form->error($model, 'typ'); ?>
	</div>
	<!-- row -->
	<div><?php
		$stati = array();
		foreach ($model->getMoeglicheStati() as $stat) {
			$stati[$stat] = IAntrag::$STATI[$stat];
		}
		echo $form->labelEx($model, 'status');
		echo $form->dropDownList($model, 'status', $stati);
		echo $form->textField($model, 'status_string', array('maxlength' => 55));
		echo $form->error($model, 'status');
		?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'name'); ?>
		<?php echo $form->textField($model, 'name'); ?>
		<?php echo $form->error($model, 'name'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'revision_name'); ?>
		<div style="display: inline-block; width: 420px;">
			<?php echo $form->textField($model, 'revision_name', array('maxlength' => 20)); ?>
			<br>
			<small>z.B. "A1", "A1neu", "S1" etc. Muss unbedingt gesetzt und eindeutig sein. Anhand dieser Angabe wird außerdem auf der Startseite sortiert.</small>
		</div>
		<?php echo $form->error($model, 'revision_name'); ?>
	</div>
	<!-- row -->
	<div>
		<?php echo $form->labelEx($model, 'datum_einreichung'); ?>
		<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
			'model'     => $model,
			'attribute' => "datum_einreichung",
			'options'   => array(
				'dateFormat' => 'yy-mm-dd',
			),
		));
		?>
		<?php echo $form->error($model, 'datum_einreichung'); ?>
	</div>

	<div>
		<?php echo $form->labelEx($model, 'datum_beschluss'); ?>
		<?php $form->widget('ext.datetimepicker.EDateTimePicker', array(
			'model'     => $model,
			'attribute' => "datum_beschluss",
			'options'   => array(
				'dateFormat' => 'yy-mm-dd',
			),
		));
		?>
		<?php echo $form->error($model, 'datum_beschluss'); ?>
	</div>

	<div>
		<?php echo $form->labelEx($model, 'notiz_intern'); ?>
		<?php echo $form->textField($model, 'notiz_intern'); ?>
		<?php echo $form->error($model, 'notiz_intern'); ?>
	</div>

	<?php if ($model->kannTextUeberarbeitenAdmin()) { ?>

		<br><br>

		<span style="font-size: 14px; font-weight: bold;">Achtung: Falls schon Änderungsanträge / Kommentare eingereicht wurden, hier weiter unten möglichst gar nichts mehr ändern. Auf keinem Fall Absätze einfügen oder löschen!</span>
		<br>

	<? if ($this->veranstaltungsreihe->subdomain == 'wiesbaden') { ?>
		<div style="margin-top: 30px;">
			<?php echo $form->labelEx($model, 'text2'); ?>
			<div>
				<?php echo $form->textArea($model, 'text2'); ?>
			</div>
			<?php echo $form->error($model, 'text2'); ?>
		</div>
	<? } ?>		<div style="margin-top: 30px;">
			<?php echo $form->labelEx($model, 'text'); ?>
			<div>
				<?php echo $form->textArea($model, 'text'); ?>
			</div>
			<?php echo $form->error($model, 'text'); ?>
		</div>
		<!-- row -->
		<div style="margin-top: 30px;">
			<?php echo $form->labelEx($model, 'begruendung'); ?>
			<div>
				<?php echo $form->textArea($model, 'begruendung'); ?>
			</div>
			<?php echo $form->error($model, 'begruendung'); ?>
		</div>
		<!-- row -->

		<script>
			$(function () {
				if ($("#Antrag_text2").length > 0) {
					ckeditor_bbcode('Antrag_text2');
				}
				ckeditor_bbcode('Antrag_text');
				ckeditor_bbcode('Antrag_begruendung');
			})
		</script>

	<?php } ?>

	<div style="overflow: auto; margin-top: 30px;">
		<label style="float: left; font-weight: bold;"><?php echo GxHtml::encode($model->getRelationLabel('antragUnterstuetzerInnen')); ?></label>

		<div style="float: left;">
			<?php
			echo UnterstuetzerInnenAdminWidget::printUnterstuetzerInnenWidget($model, "antragUnterstuetzerInnen");
			?>
		</div>
	</div>
	<div class="saveholder" style=" margin-top: 30px;">
		<?php
		echo GxHtml::submitButton(Yii::t('app', 'Save'), array("class" => "btn btn-primary"));
		$this->endWidget();
		?>
	</div>
</div><!-- form -->