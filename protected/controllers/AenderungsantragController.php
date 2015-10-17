<?php

class AenderungsantragController extends AntragsgruenController
{
    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     * @return Aenderungsantrag
     */
    private function getValidatedParamObjects($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id)
    {
        $aenderungsantrag_id = IntVal($aenderungsantrag_id);
        /** @var Aenderungsantrag $aenderungsantrag */
        $aenderungsantrag = Aenderungsantrag::model()->findByPk($aenderungsantrag_id);
        if (is_null($aenderungsantrag) || $aenderungsantrag->status == IAntrag::$STATUS_GELOESCHT) {
            $this->render("../veranstaltung/error", array("message" => "Der angegebene Änderungsantrag wurde nicht gefunden."));
            Yii::app()->end(404);
        }

        $antrag_id = IntVal($antrag_id);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);
        if (is_null($antrag) || $antrag->status == IAntrag::$STATUS_GELOESCHT) {
            $this->render("../veranstaltung/error", array("message" => "Der angegebene Antrag wurde nicht gefunden."));
            Yii::app()->end(404);
        }

        $this->veranstaltung = $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag, $aenderungsantrag);
        $this->testeWartungsmodus();

        return $aenderungsantrag;
    }

    /**
     * @param Aenderungsantrag $aenderungsantrag
     * @param int $kommentar_id
     */
    private function performAnzeigeActions($aenderungsantrag, $kommentar_id)
    {
        if (AntiXSS::isTokenSet("komm_del")) {
            /** @var AenderungsantragKommentar $komm */
            $komm = AenderungsantragKommentar::model()->findByPk(AntiXSS::getTokenVal("komm_del"));
            if ($komm->aenderungsantrag_id == $aenderungsantrag->id && $komm->kannLoeschen(Yii::app()->user) && $komm->status == IKommentar::$STATUS_FREI) {
                $komm->status = IKommentar::$STATUS_GELOESCHT;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");

                $benachrichtigt = array();
                foreach ($aenderungsantrag->antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
                    if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                        $abo->person->benachrichtigenKommentar($komm);
                        $benachrichtigt[] = $abo->person_id;
                    }
                }
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
        }

        if (AntiXSS::isTokenSet("komm_freischalten") && $kommentar_id > 0) {
            /** @var AenderungsantragKommentar $komm */
            $komm = AenderungsantragKommentar::model()->findByPk($kommentar_id);
            if ($komm->aenderungsantrag_id == $aenderungsantrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $aenderungsantrag->antrag->veranstaltung->isAdminCurUser()) {
                $komm->status = IKommentar::$STATUS_FREI;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde freigeschaltet.");
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
        }

        if (AntiXSS::isTokenSet("komm_nicht_freischalten") && $kommentar_id > 0) {
            /** @var AenderungsantragKommentar $komm */
            $komm = AenderungsantragKommentar::model()->findByPk($kommentar_id);
            if ($komm->aenderungsantrag_id == $aenderungsantrag->id && $komm->status == IKommentar::$STATUS_NICHT_FREI && $aenderungsantrag->antrag->veranstaltung->isAdminCurUser()) {
                $komm->status = IKommentar::$STATUS_GELOESCHT;
                $komm->save();
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gelöscht.");
            } else {
                Yii::app()->user->setFlash("error", "Kommentar nicht gefunden oder keine Berechtigung.");
            }
        }

        if (AntiXSS::isTokenSet("mag") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAenderungsantragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) {
                if ($unt->unterstuetzerIn_id == $userid) {
                    $unt->delete();
                }
            }
            $unt                      = new AenderungsantragUnterstuetzerInnen();
            $unt->aenderungsantrag_id = $aenderungsantrag->id;
            $unt->unterstuetzerIn_id  = $userid;
            $unt->rolle               = "mag";
            $unt->kommentar           = "";
            if ($unt->save()) {
                Yii::app()->user->setFlash("success", "Du unterstützt diesen Änderungsantrag nun.");
            } else {
                Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            }
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id, "aenderungsantrag_id" => $aenderungsantrag->id)));
        }

        if (AntiXSS::isTokenSet("magnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAenderungsantragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) {
                if ($unt->unterstuetzerIn_id == $userid) {
                    $unt->delete();
                }
            }
            $unt                      = new AenderungsantragUnterstuetzerInnen();
            $unt->aenderungsantrag_id = $aenderungsantrag->id;
            $unt->unterstuetzerIn_id  = $userid;
            $unt->rolle               = "magnicht";
            $unt->kommentar           = "";
            $unt->save();
            if ($unt->save()) {
                Yii::app()->user->setFlash("success", "Du lehnst diesen Änderungsantrag nun ab.");
            } else {
                Yii::app()->user->setFlash("error", "Ein (seltsamer) Fehler ist aufgetreten.");
            }
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id, "aenderungsantrag_id" => $aenderungsantrag->id)));
        }

        if (AntiXSS::isTokenSet("dochnicht") && $this->veranstaltung->getPolicyUnterstuetzen()->checkAenderungsantragSubmit()) {
            $userid = Yii::app()->user->getState("person_id");
            foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) {
                if ($unt->unterstuetzerIn_id == $userid) {
                    $unt->delete();
                }
            }
            Yii::app()->user->setFlash("success", "Du stehst diesem Änderungsantrag wieder neutral gegenüber.");
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id, "aenderungsantrag_id" => $aenderungsantrag->id)));
        }
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     * @param int $kommentar_id
     */
    public function actionAnzeige($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id, $kommentar_id = 0)
    {
        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        $this->layout = '//layouts/column2';

        if (!$aenderungsantrag) {
            Yii::app()->user->setFlash("error", "Eine ungültige URL wurde aufgerufen");
            $this->redirect($this->createUrl("veranstaltung/veranstaltung"));
        }

        $this->performAnzeigeActions($aenderungsantrag, $kommentar_id);

        $kommentare_offen = array();

        if (AntiXSS::isTokenSet("kommentar_schreiben") && $aenderungsantrag->antrag->veranstaltung->darfEroeffnenKommentar()) {
            $zeile = IntVal($_REQUEST["absatz_nr"]);

            $person        = $_REQUEST["Person"];
            $person["typ"] = Person::$TYP_PERSON;

            if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->kommentar_neu_braucht_email && trim($person["email"]) == "") {
                Yii::app()->user->setFlash("error", "Bitte gib deine E-Mail-Adresse an.");
                $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id, "aenderungsantrag_id" => $aenderungsantrag->id)));
            }
            $model_person = static::getCurrenPersonOrCreateBySubmitData($person, Person::$STATUS_UNCONFIRMED, false);

            $kommentar                      = new AenderungsantragKommentar();
            $kommentar->attributes          = $_REQUEST["AenderungsantragKommentar"];
            $kommentar->absatz              = $zeile;
            $kommentar->datum               = new CDbExpression('NOW()');
            $kommentar->verfasserIn         = $model_person;
            $kommentar->verfasserIn_id      = $model_person->id;
            $kommentar->aenderungsantrag    = $aenderungsantrag;
            $kommentar->aenderungsantrag_id = $aenderungsantrag_id;
            $kommentar->status              = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? IKommentar::$STATUS_NICHT_FREI : IKommentar::$STATUS_FREI);

            $kommentare_offen[] = $zeile;

            if ($kommentar->save()) {
                $add = ($this->veranstaltung->getEinstellungen()->freischaltung_kommentare ? " Er wird nach einer kurzen Prüfung freigeschaltet und damit sichtbar." : "");
                Yii::app()->user->setFlash("success", "Der Kommentar wurde gespeichert." . $add);

                if ($this->veranstaltung->admin_email != "" && $kommentar->status == IKommentar::$STATUS_NICHT_FREI) {
                    $kommentar_link = $kommentar->getLink(true);
                    $mails          = explode(",", $this->veranstaltung->admin_email);
                    $from_name      = veranstaltungsspezifisch_email_from_name($this->veranstaltung);
                    $mail_text      = "Es wurde ein neuer Kommentar zum Änderungsantrag \"" . $aenderungsantrag->revision_name . " zu " . $aenderungsantrag->antrag->revision_name . " - " . $aenderungsantrag->antrag->name . "\" verfasst (nur eingeloggt sichtbar):\n" .
                        "Link: " . $kommentar_link;

                    foreach ($mails as $mail) {
                        if (trim($mail) != "") {
                            AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Kommentar - bitte freischalten.", $mail_text, $from_name);
                        }
                    }
                }

                if ($kommentar->status == IKommentar::$STATUS_FREI) {
                    $benachrichtigt = array();
                    foreach ($aenderungsantrag->antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
                        if ($abo->kommentare && !in_array($abo->person_id, $benachrichtigt)) {
                            $abo->person->benachrichtigenKommentar($kommentar);
                            $benachrichtigt[] = $abo->person_id;
                        }
                    }
                }

                $this->redirect($kommentar->getLink());
            } else {
                foreach ($kommentar->getErrors() as $key => $val) {
                    foreach ($val as $val2) {
                        Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
                    }
                }
                foreach ($model_person->getErrors() as $key => $val) {
                    foreach ($val as $val2) {
                        Yii::app()->user->setFlash("error", "Kommentar konnte nicht angelegt werden: $key: $val2");
                    }
                }
            }
        }
        if ($kommentar_id > 0) {
            if ($aenderungsantrag->kommentar_legacy) {
                $abs = $aenderungsantrag->getAntragstextParagraphs_flat();
                foreach ($abs as $ab) {
                    /** @var AntragAbsatz $ab */
                    foreach ($ab->kommentare as $komm) {
                        if ($komm->id == $kommentar_id) {
                            $kommentare_offen[] = $ab->absatz_nr;
                        }
                    }
                }
            } else {
                $abs2 = $aenderungsantrag->getAntragstextParagraphs_diff();
                foreach ($abs2 as $i => $ab2) {
                    if ($ab2 === null) {
                        continue;
                    }
                    /** @var AenderungsantragAbsatz $ab2 */
                    foreach ($ab2->kommentare as $komm) {
                        if ($komm->id == $kommentar_id) {
                            $kommentare_offen[] = $i;
                        }
                    }
                }
            }
        }

        $hiddens       = array();
        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("kommentar_schreiben");
        } else {
            $hiddens[AntiXSS::createToken("kommentar_schreiben")] = "1";
        }


        if (Yii::app()->user->isGuest) {
            $kommentar_person = new Person();
        } else {
            $kommentar_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
        }
        $kommentar_person->setEmailRequired($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->kommentar_neu_braucht_email);

        $support_status = "";
        if (!Yii::app()->user->isGuest) {
            foreach ($aenderungsantrag->aenderungsantragUnterstuetzerInnen as $unt) {
                if ($unt->person->id == Yii::app()->user->getState("person_id")) {
                    $support_status = $unt->rolle;
                }
            }
        }

        $anzeige_opts = array(
            "aenderungsantrag" => $aenderungsantrag,
            "edit_link"        => $aenderungsantrag->binInitiatorIn(),
            "admin_edit"       => ($this->veranstaltung->isAdminCurUser() ? $this->createUrl("/admin/aenderungsantraege/update", array("id" => $aenderungsantrag_id)) : null),
            "kommentare_offen" => $kommentare_offen,
            "kommentar_person" => $kommentar_person,
            "komm_del_link"    => $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag_id, AntiXSS::createToken("komm_del") => "#komm_id#")),
            "hiddens"          => $hiddens,
            "js_protection"    => $js_protection,
            "support_status"   => $support_status,
            "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
        );

        if ($aenderungsantrag->kommentar_legacy) {
            $this->render("anzeige_legacy", $anzeige_opts);
        } else {
            $this->render("anzeige", $anzeige_opts);
        }
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     * @param bool $long_name
     */
    public function actionPdf($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id, $long_name = false)
    {
        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        $this->renderPartial("pdf", array(
            "model"        => $aenderungsantrag,
            "sprache"      => $aenderungsantrag->antrag->veranstaltung->getSprache(),
            "diff_ansicht" => false,
            "long_name"    => $long_name,
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     * @param bool $long_name
     */
    public function actionPdfDiff($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id, $long_name = false)
    {
        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        $this->renderPartial("pdf", array(
            "model"        => $aenderungsantrag,
            "sprache"      => $aenderungsantrag->antrag->veranstaltung->getSprache(),
            "diff_ansicht" => true,
            "long_name"    => $long_name,
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     */
    public function actionBearbeiten($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id)
    {
        $this->layout = '//layouts/column2';

        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        if (!$aenderungsantrag->binInitiatorIn()) {
            Yii::app()->user->setFlash("error", "Kein Zugriff auf den Änderungsantrag");
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag_id)));
        }

        if (AntiXSS::isTokenSet("ae_del")) {
            $aenderungsantrag->status = Aenderungsantrag::$STATUS_ZURUECKGEZOGEN;
            if ($aenderungsantrag->save()) {
                Yii::app()->user->setFlash("success", "Der Änderungsantrag wurde zurückgezogen.");
                $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $aenderungsantrag->antrag_id)));
            } else {
                Yii::app()->user->setFlash("error", "Der Änderungsantrag konnte nicht zurückgezogen werden.");
            }
        }

        $this->render("bearbeiten_start", array(
            "aenderungsantrag" => $aenderungsantrag,
            "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
        ));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     */
    public function actionAendern($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id)
    {
        $this->layout = '//layouts/column2';

        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        if (!$aenderungsantrag->binInitiatorIn()) {
            Yii::app()->user->setFlash("error", "Kein Zugriff auf den Änderungsantrag");
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag_id)));
        }

        if (AntiXSS::isTokenSet("antragbearbeiten")) {
            echo "Speichern";
            die();
        }

        $antrag = $aenderungsantrag->antrag;

        $hiddens = array("antrag_id" => $antrag->id);

        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("antragbearbeiten");
        } else {
            $hiddens[AntiXSS::createToken("antragbearbeiten")] = "1";
        }

        $antragstellerIn = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));

        $this->render('bearbeiten_form', array(
            "mode"             => "bearbeiten",
            "antrag"           => $antrag,
            "aenderungsantrag" => $aenderungsantrag,
            "antragstellerIn"  => $antragstellerIn,
            "hiddens"          => $hiddens,
            "js_protection"    => $js_protection,
            "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
        ));


    }


    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $aenderungsantrag_id
     */
    public function actionNeuConfirm($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $aenderungsantrag_id)
    {
        $this->layout = '//layouts/column2';

        $aenderungsantrag = $this->getValidatedParamObjects($veranstaltungsreihe_id, $veranstaltung_id, $antrag_id, $aenderungsantrag_id);

        if ($aenderungsantrag->status != Aenderungsantrag::$STATUS_UNBESTAETIGT) {
            $this->redirect($this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag_id)));
        }

        if (AntiXSS::isTokenSet("antragbestaetigen")) {

            $freischaltung = $aenderungsantrag->antrag->veranstaltung->getEinstellungen()->freischaltung_aenderungsantraege;
            if ($freischaltung) {
                $aenderungsantrag->status = Aenderungsantrag::$STATUS_EINGEREICHT_UNGEPRUEFT;
            } else {
                $aenderungsantrag->status        = Aenderungsantrag::$STATUS_EINGEREICHT_GEPRUEFT;
                $aenderungsantrag->revision_name = $aenderungsantrag->naechsteAenderungsRevNr();
            }
            $aenderungsantrag->save();

            if ($aenderungsantrag->antrag->veranstaltung->admin_email != "") {
                $mails     = explode(",", $aenderungsantrag->antrag->veranstaltung->admin_email);
                $from_name = veranstaltungsspezifisch_email_from_name($this->veranstaltung);
                $mail_text = "Es wurde ein neuer Änderungsantrag zum Antrag \"" . $aenderungsantrag->antrag->name . "\" eingereicht.\n" .
                    "Link: " . yii::app()->getBaseUrl(true) . $this->createUrl("aenderungsantrag/anzeige", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag_id));

                foreach ($mails as $mail) {
                    if (trim($mail) != "") {
                        AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN, trim($mail), null, "Neuer Änderungsantrag", $mail_text, $from_name);
                    }
                }
            }

            if ($aenderungsantrag->status == Antrag::$STATUS_EINGEREICHT_GEPRUEFT) {
                $benachrichtigt = array();
                foreach ($aenderungsantrag->antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
                    if ($abo->aenderungsantraege && !in_array($abo->person_id, $benachrichtigt)) {
                        $abo->person->benachrichtigenAenderungsantrag($aenderungsantrag);
                        $benachrichtigt[] = $abo->person_id;
                    }
                }
            }

            $this->render("neu_submitted", array(
                "aenderungsantrag" => $aenderungsantrag,
                "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
            ));

        } else {

            $this->render('neu_confirm', array(
                "aenderungsantrag" => $aenderungsantrag,
                "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
            ));

        }

    }


    /**
     *
     */
    public function actionAjaxCalcDiff()
    {
        if (!isset($_REQUEST["absaetze"])) {
            return;
        }

        $this->testeWartungsmodus();

        $antrag_id = IntVal($_REQUEST["antrag_id"]);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);

        $diffs = array();
        /** @var array|AntragAbsatz[] $pars */
        $pars = $antrag->getParagraphs();
        foreach ($_REQUEST["absaetze"] as $absatznr => $text_neu) {
            $diffs[$absatznr] = DiffUtils::renderBBCodeDiff2HTML($pars[$absatznr]->str_bbcode, $text_neu);
        }

        $this->renderPartial('ajax_diff', array("diffs" => $diffs));
    }

    /**
     * @param string $veranstaltungsreihe_id
     * @param string $veranstaltung_id
     * @param int $antrag_id
     * @param int $adoptInitiators
     */
    public function actionNeu($veranstaltungsreihe_id = "", $veranstaltung_id, $antrag_id, $adoptInitiators = 0)
    {
        $this->layout = '//layouts/column2';

        $this->testeWartungsmodus();

        $antrag_id = IntVal($antrag_id);
        /** @var Antrag $antrag */
        $antrag = Antrag::model()->findByPk($antrag_id);

        $this->loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id, $antrag);

        if (!$antrag->veranstaltung->getPolicyAenderungsantraege()->checkCurUserHeuristically()) {
            $msg = $antrag->veranstaltung->getPolicyAenderungsantraege()->getPermissionDeniedMsg();
            Yii::app()->user->setFlash("error", "Es kann kein Änderungsantrag werden: " . $msg);
            $this->redirect($this->createUrl("antrag/anzeige", array("antrag_id" => $antrag->id)));
        }

        $aenderungsantrag                             = new Aenderungsantrag();
        $aenderungsantrag->aenderung_first_line_cache = -1;
        $aenderungsantrag->antrag                     = $antrag;
        $aenderungsantrag->antrag_id                  = $antrag->id;
        $aenderungsantrag->status                     = Aenderungsantrag::$STATUS_UNBESTAETIGT;
        $aenderungsantrag->status_string              = "";

        $unterstuetzerInnen = array();

        $changed = false;

        if (AntiXSS::isTokenSet("antragneu")) {

            $aenderungsantrag->name_neu           = $_REQUEST["Aenderungsantrag"]["name_neu"];
            $aenderungsantrag->begruendung_neu    = "";
            $aenderungsantrag->aenderung_metatext = HtmlBBcodeUtils::html_normalize($_REQUEST["ae_metatext"]);

            if ($aenderungsantrag->antrag->veranstaltung->getEinstellungen()->begruendung_in_html && isset($_REQUEST["ae_begruendung_html"])) {
                $aenderungsantrag->aenderung_begruendung_html = 1;
                $aenderungsantrag->aenderung_begruendung      = HtmlBBcodeUtils::html_normalize($_REQUEST["ae_begruendung"]);
            } else {
                $aenderungsantrag->aenderung_begruendung_html = 0;
                $aenderungsantrag->aenderung_begruendung      = HtmlBBcodeUtils::bbcode_normalize($_REQUEST["ae_begruendung"]);
            }

            if ($aenderungsantrag->name_neu != $antrag->name) {
                $changed = true;
            }
            if (trim($aenderungsantrag->aenderung_metatext) != "") {
                $changed = true;
            }

            $orig_absaetze = $antrag->getParagraphs();
            $neue_absaetze = array();
            $neuer_text    = "";
            for ($i = 0; $i < count($orig_absaetze); $i++) {
                /** @var AntragAbsatz $abs */
                $abs = $orig_absaetze[$i];
                if (isset($_REQUEST["change_text"][$i])) {
                    $abs_text          = HtmlBBcodeUtils::bbcode_normalize($_REQUEST["neu_text"][$i]);
                    $neue_absaetze[$i] = $abs_text;
                    $neuer_text .= $abs_text . "\n\n";
                    $changed = true;
                } else {
                    $neue_absaetze[$i] = "";
                    $neuer_text .= $abs->str_bbcode . "\n\n";
                }
            }

            if ($changed) {
                $aenderungsantrag->setDiffParagraphs($neue_absaetze);

                $diff      = DiffUtils::getTextDiffMitZeilennummern($antrag->text, $neuer_text, $antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
                $diff_text = "";

                if ($aenderungsantrag->name_neu != $antrag->name) {
                    $diff_text .= "Neuer Titel des Antrags:\n[QUOTE]" . $aenderungsantrag->name_neu . "[/QUOTE]\n\n";
                }
                if (trim($aenderungsantrag->aenderung_metatext) != "") {
                    $diff_text = $aenderungsantrag->aenderung_metatext . "\n\n";
                }
                $diff_text .= DiffUtils::diff2text($diff, $antrag->getFirstLineNo());

                $aenderungsantrag->aenderung_text    = $diff_text;
                $aenderungsantrag->datum_einreichung = new CDbExpression('NOW()');

            } else {
                Yii::app()->user->setFlash("error", "Es wurde nichts am Text geändert.");
            }

            if (!$this->veranstaltung->getPolicyAenderungsantraege()->checkAenderungsantragSubmit()) {
                Yii::app()->user->setFlash("error", "Keine Berechtigung zum Anlegen von Änderungsanträgen.");
                $changed = false;
            }
        }

        $hiddens = array("antrag_id" => $antrag->id);

        $js_protection = Yii::app()->user->isGuest;
        if ($js_protection) {
            $hiddens["form_token"] = AntiXSS::createToken("antragneu");
        } else {
            $hiddens[AntiXSS::createToken("antragneu")] = "1";
        }


        if ($changed) {

            if (!$aenderungsantrag->save()) {
                foreach ($aenderungsantrag->getErrors() as $val) {
                    foreach ($val as $val2) {
                        Yii::app()->user->setFlash("error", "Änderungsantrag konnte nicht angelegt werden: " . $val2);
                    }
                }
                $antragstellerIn = (Yii::app()->user->isGuest ? null : Person::model()->findByAttributes(array("auth" => Yii::app()->user->id)));
                if ($antragstellerIn === null) {
                    $antragstellerIn = new Person();
                }
                $this->render('bearbeiten_form', array(
                    "mode"             => "neu",
                    "antrag"           => $antrag,
                    "aenderungsantrag" => $aenderungsantrag,
                    "antragstellerIn"  => $antragstellerIn,
                    "unterstuetzerInnen" => $unterstuetzerInnen,
                    "hiddens"          => $hiddens,
                    "js_protection"    => $js_protection,
                    "sprache"          => $aenderungsantrag->antrag->veranstaltung->getSprache(),
                ));
                return;
            }

            $this->veranstaltung->getPolicyAenderungsantraege()->submitAntragsstellerInView_Aenderungsantrag($aenderungsantrag);

            $this->redirect($this->createUrl("aenderungsantrag/neuConfirm", array("antrag_id" => $antrag_id, "aenderungsantrag_id" => $aenderungsantrag->id)));

        } else {
            if (Yii::app()->user->isGuest || $this->veranstaltung->isAdminCurUser()) {
                $antragstellerIn      = new Person();
                $antragstellerIn->typ = Person::$TYP_PERSON;
            } else {
                $antragstellerIn = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
            }

            if ($adoptInitiators > 0 && $this->veranstaltung->isAdminCurUser()) {
                $templateAmendment = $this->veranstaltung->getAmendment($adoptInitiators);
                if ($templateAmendment) {
                    foreach ($templateAmendment->aenderungsantragUnterstuetzerInnen as $unt) {
                        if ($unt->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN) {
                            $antragstellerIn = $unt->person;
                        }
                        if ($unt->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) {
                            $unterstuetzerInnen[] = $unt->person;
                        }
                    }
                }
            }

            $aenderungsantrag->name_neu = $antrag->name;


            $this->render('bearbeiten_form', array(
                "mode"               => "neu",
                "antrag"             => $antrag,
                "aenderungsantrag"   => $aenderungsantrag,
                "antragstellerIn"    => $antragstellerIn,
                "unterstuetzerInnen" => $unterstuetzerInnen,
                "hiddens"            => $hiddens,
                "js_protection"      => $js_protection,
                "sprache"            => $aenderungsantrag->antrag->veranstaltung->getSprache(),
            ));
        }
    }

}
