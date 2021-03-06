<?php

class TexteController extends GxController {


	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 */
	public function actionView($veranstaltungsreihe_id = "", $veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) return;

		/** @var Texte $text  */
		$text = $this->loadModel($id, 'Texte');
		if ($text->veranstaltung->id != $this->veranstaltung->id) return;

		$this->render('view', array(
			'model' => $this->loadModel($id, 'Texte'),
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionCreate($veranstaltungsreihe_id = "", $veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var $model Texte */
		$model = new Texte;

		if (isset($_REQUEST["key"])) $model->text_id = $_REQUEST["key"];

		if (isset($_POST['Texte'])) {
			$model->setAttributes($_POST['Texte'], false);
			$model->veranstaltung = $this->veranstaltung;
			$model->veranstaltung_id = $this->veranstaltung->id;
			$model->edit_datum = new CDbExpression('NOW()');

			if ($model->save()) {
				if (Yii::app()->getRequest()->getIsAjaxRequest())
					Yii::app()->end();
				else
					$this->redirect(array('view', 'id' => $model->id));
			}
		} elseif (isset($_REQUEST["key"])) {
			$stdtext = $this->veranstaltung->getStandardtext($_REQUEST["key"]);
			$model->text = $stdtext->getText();
		}

		$this->render('create', array( 'model' => $model));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 */
	public function actionUpdate($veranstaltungsreihe_id = "", $veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var Texte $model  */
		$model = $this->loadModel($id, 'Texte');
		if (is_null($model)) {
			Yii::app()->user->setFlash("error", "Der angegebene Text wurde nicht gefunden.");
			$this->redirect($this->createUrl("/admin/texte/"));
		}
		if ($model->veranstaltung->id != $this->veranstaltung->id) {
			Yii::app()->user->setFlash("error", "Dieser Text gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("/admin/texte/"));
		}

		if (isset($_POST['Texte'])) {
			$model->setAttributes($_POST['Texte']);

			$model->edit_datum = new CDbExpression('NOW()');

			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}

		$this->render('update', array(
				'model' => $model,
				));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param int $id
	 * @throws CHttpException
	 */
	public function actionDelete($veranstaltungsreihe_id = "", $veranstaltung_id, $id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		/** @var Texte $text  */
		$text = $this->loadModel($id, 'Texte');
		if ($text->veranstaltung->id != $this->veranstaltung->id) return;

		if (Yii::app()->getRequest()->getIsPostRequest()) {
			$text->delete();

			if (!Yii::app()->getRequest()->getIsAjaxRequest())
				$this->redirect(array('admin'));
		} else
			throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$criteria = new CDbCriteria;
		$criteria->compare('veranstaltung_id', $this->veranstaltung->id);
		$dataProvider = new CActiveDataProvider('Texte', array("criteria" => $criteria));
		$this->render('index', array(
			'dataProvider' => $dataProvider,
		));
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 */
	public function actionAdmin($veranstaltungsreihe_id = "", $veranstaltung_id) {
		$this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
		if (!$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));

		$model = new Texte('search');
		$model->unsetAttributes();

		if (isset($_GET['Texte']))
			$model->setAttributes($_GET['Texte']);

		$model->veranstaltung_id = $this->veranstaltung->id;
		$model->veranstaltung = $this->veranstaltung;

		$this->render('admin', array(
			'model' => $model,
		));
	}

}