<?php

class AntraegeController extends GxController
{

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $id
     * @throws CException
     * @throws Exception
     */
    public function actionUpdate($veranstaltungsreihe_id = "", $veranstaltung_id, $id)
    {
        $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
        if (!$this->veranstaltung->isAdminCurUser()) {
            $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));
        }

        /** @var $model Antrag */
        $model = Antrag::model()->with("antragUnterstuetzerInnen", "antragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
        if (is_null($model)) {
            Yii::app()->user->setFlash("error", "Der angegebene Antrag wurde nicht gefunden.");
            $this->redirect($this->createUrl("admin/antraege"));
        }
        if ($model->veranstaltung_id != $this->veranstaltung->id) {
            return;
        }

        $this->performAjaxValidation($model, 'antrag-form');

        $messages = array();

        if (AntiXSS::isTokenSet("antrag_freischalten")) {
            $newvar               = AntiXSS::getTokenVal("antrag_freischalten");
            $model->adminFreischalten($newvar);
            Yii::app()->user->setFlash("success", "Der Antrag wurde freigeschaltet.");
        }

        if (isset($_POST['Antrag'])) {
            $fixed_fields = $fixed_fields_pre = array();
            if (!$model->kannTextUeberarbeitenAdmin()) {
                $fixed_fields = array(
                    "text_unveraenderlich", "text", "begruendung",
                );
            }
            foreach ($fixed_fields as $field) {
                $fixed_fields_pre[$field] = $model->$field;
            }

            if (!in_array($_POST['Antrag']['status'], $model->getMoeglicheStati())) {
                throw new Exception("Status-Übergang ungültig");
            }
            $revision_name = $model->revision_name;
            $model->setAttributes($_POST['Antrag'], false);

            if ($model->revision_name != $revision_name && $revision_name != "") {
                foreach ($this->veranstaltung->antraege as $ant) {
                    if ($ant->id != $model->id && $ant->revision_name == $model->revision_name && $ant->status != Antrag::$STATUS_GELOESCHT) {
                        // Zurücksetzen + Warnung
                        $messages[]           = "Das vergebene Antragskürzel \"" . $model->revision_name . "\" wird bereits von einem anderen Antrag verwendet.";
                        $model->revision_name = $revision_name;
                    }
                }
            }

            foreach ($fixed_fields_pre as $field => $val) {
                $model->$field = $val;
            }

            Yii::import('ext.datetimepicker.EDateTimePicker');
            $model->datum_einreichung = EDateTimePicker::parseInput($_POST["Antrag"], "datum_einreichung");
            $model->datum_beschluss   = EDateTimePicker::parseInput($_POST["Antrag"], "datum_beschluss");

            $relatedData = array();

            if ($model->saveWithRelated($relatedData)) {
                $model->veranstaltung->resetLineCache();
                UnterstuetzerInnenAdminWidget::saveUnterstuetzerInnenWidget($model, $messages, "AntragUnterstuetzerInnen", "antrag_id", $id);

                $model = Antrag::model()->with("antragUnterstuetzerInnen", "antragUnterstuetzerInnen.person")->findByPk($id, '', array("order" => "`person`.`name"));
            }
        }

        $this->render('update', array(
            'model'    => $model,
            'messages' => $messages,
        ));
    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $id
     * @throws CHttpException
     */
    public function actionDelete($veranstaltungsreihe_id = "", $veranstaltung_id, $id)
    {
        $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
        if (!$this->veranstaltung->isAdminCurUser()) {
            $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));
        }

        /** @var Antrag $antrag */
        $antrag = $this->loadModel($id, 'Antrag');
        if ($antrag->veranstaltung_id != $this->veranstaltung->id) {
            return;
        }

        if (Yii::app()->getRequest()->getIsPostRequest()) {
            $antrag->status = IAntrag::$STATUS_GELOESCHT;
            $antrag->save();

            if (!Yii::app()->getRequest()->getIsAjaxRequest()) {
                $this->redirect(array('index'));
            }
        } else {
            throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
        }
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int|null $status
     */
    public function actionIndex($veranstaltungsreihe_id = "", $veranstaltung_id, $status = null)
    {
        $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
        if (!$this->veranstaltung->isAdminCurUser()) {
            $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));
        }

        $suche = new AdminAntragFilterForm($this->veranstaltung, $this->veranstaltung->antraege, false);
        if (isset($_REQUEST["Search"])) {
            $suche->setAttributes($_REQUEST["Search"]);
        }
        $antraege = $suche->getFilteredMotions();

        $this->render('index', array(
            'antraege'               => $antraege,
            'status_curr'            => $status,
            'suche'                  => $suche,
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     */
    public function actionAdmin($veranstaltungsreihe_id = "", $veranstaltung_id)
    {
        $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id);
        if (!$this->veranstaltung->isAdminCurUser()) {
            $this->redirect($this->createUrl("/veranstaltung/login", array("back" => yii::app()->getRequest()->requestUri)));
        }

        $model = new Antrag('search');
        $model->unsetAttributes();

        if (isset($_GET['Antrag'])) {
            $model->setAttributes($_GET['Antrag']);
        }

        $model->veranstaltung_id = $this->veranstaltung->id;
        $model->veranstaltung    = $this->veranstaltung;

        $this->render('admin', array(
            'model' => $model,
        ));
    }

}
