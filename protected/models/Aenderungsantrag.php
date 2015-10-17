<?php

/**
 * @property integer $id
 * @property integer $antrag_id
 * @property string $revision_name
 * @property string $name_neu
 * @property string $text_neu
 * @property string $begruendung_neu
 * @property string $aenderung_metatext
 * @property string $aenderung_text
 * @property string $aenderung_text2
 * @property string $aenderung_begruendung
 * @property integer $aenderung_begruendung_html
 * @property integer $aenderung_first_line_cache
 * @property string $flp_rel_cache
 * @property string $flp_abs_cache
 * @property string $datum_einreichung
 * @property string $datum_beschluss
 * @property integer $status
 * @property string $status_string
 * @property string $notiz_intern
 * @property int $kommentar_legacy
 * @property integer $text_unveraenderlich
 *
 * @property Antrag $antrag
 * @property AenderungsantragKommentar[] $aenderungsantragKommentare
 * @property AenderungsantragUnterstuetzerInnen[] $aenderungsantragUnterstuetzerInnen
 */
class Aenderungsantrag extends IAntrag
{
	private $absaetze = null;

	/**
	 * @var string $className
	 * @return Aenderungsantrag
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return 'aenderungsantrag';
	}

	/**
	 * @param int $n
	 * @return string
	 */
	public static function label($n = 1)
	{
		return Yii::t('app', 'Aenderungsantrag|Aenderungsantraege', $n);
	}

	/**
	 * @return string
	 */
	public static function representingColumn()
	{
		return 'text_neu';
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array('text_neu, aenderung_text, datum_einreichung, status, status', 'required'),
			array('antrag_id, status, aenderung_first_line_cache, kommentar_legacy, text_unveraenderlich, aenderung_begruendung_html', 'numerical', 'integerOnly' => true),
			array('revision_name', 'length', 'max' => 45),
			array('status_string', 'length', 'max' => 55),
			array('name_neu, datum_beschluss, aenderung_metatext, notiz_intern', 'safe'),
			array('antrag_id, revision_name, name_neu, datum_beschluss, aenderung_begruendung, aenderung_begruendung_html, flp_abs_cache, flp_rel_cache', 'default', 'setOnEmpty' => true, 'value' => null),
		);

	}

	/**
	 * @return array
	 */
	public function relations()
	{
		return array(
			'antrag'                             => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
			'aenderungsantragKommentare'         => array(self::HAS_MANY, 'AenderungsantragKommentar', 'aenderungsantrag_id'),
			'aenderungsantragUnterstuetzerInnen' => array(
				self::HAS_MANY, 'AenderungsantragUnterstuetzerInnen', 'aenderungsantrag_id',
				'order' => "aenderungsantragUnterstuetzerInnen.position ASC"
			),
		);
	}

	/**
	 * @return array
	 */
	public function pivotModels()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'id'                                 => Yii::t('app', 'ID'),
			'antrag_id'                          => null,
			'revision_name'                      => Yii::t('app', 'Revision Name'),
			'name_neu'                           => Yii::t('app', 'Name Neu'),
			'text_neu'                           => Yii::t('app', 'Text Neu'),
			'begruendung_neu'                    => Yii::t('app', 'Begruendung Neu'),
			'aenderung_metatext'                 => Yii::t('app', 'Metabeschreibung der Änderung'),
			'aenderung_text'                     => Yii::t('app', 'Änderung: Text'),
            'aenderung_text2'                    => Yii::t('app', 'Änderung: Text 2'),
			'aenderung_begruendung'              => Yii::t('app', 'Änderung: Begründung'),
			'aenderung_begruendung_html'         => Yii::t('app', 'Änderung: Begründung in HTML'),
			'aenderung_first_line_cache'         => "Cache: erste Zeilennummer",
			'datum_einreichung'                  => Yii::t('app', 'Datum Einreichung'),
			'datum_beschluss'                    => Yii::t('app', 'Datum Beschluss'),
			'status'                             => Yii::t('app', 'Status'),
			'status_string'                      => Yii::t('app', 'Status String'),
			'kommentar_legacy'                   => Yii::t('app', 'Altes Kommentarsystem'),
			'text_unveraenderlich'               => Yii::t('app', 'Text Unveränderlich'),
			'notiz_intern'                       => Yii::t('app', 'Interne Notiz'),
			'antrag'                             => null,
			'aenderungsantragKommentare'         => null,
			'aenderungsantragUnterstuetzerInnen' => Yii::t('app', 'AntragstellerInnen'),
			'flp_abs_cache'                      => '',
			'flp_rel_cache'                      => '',
		);
	}

	/**
	 * @param bool $runValidation
	 * @param null $attributes
	 * @return bool
	 */
	public function save($runValidation = true, $attributes = null)
	{
		Yii::app()->cache->delete("pdf_ae_" . $this->antrag->veranstaltung->id);

		return parent::save($runValidation, $attributes);
	}

	/**
	 * @param Antrag $antrag
	 * @param int $anz_absaetze_neu
	 * @param array $absatz_mapping
	 * @return Aenderungsantrag
	 */
	public function aufrechterhaltenBeiNeuemAntrag($antrag, $anz_absaetze_neu, $absatz_mapping)
	{
		$neuer_ae                             = new Aenderungsantrag();
		$neuer_ae->antrag_id                  = $antrag->id;
		$neuer_ae->revision_name              = $this->revision_name;
		$neuer_ae->name_neu                   = $this->name_neu;
		$neuer_ae->begruendung_neu            = $this->begruendung_neu;
		$neuer_ae->aenderung_begruendung      = $this->aenderung_begruendung;
		$neuer_ae->datum_einreichung          = $this->datum_einreichung;
		$neuer_ae->aenderung_first_line_cache = -1;
		$neuer_ae->status_string              = "";
		$neuer_ae->status                     = IAntrag::$STATUS_EINGEREICHT_GEPRUEFT;

		$text_neu = array();
		for ($i = 0; $i < $anz_absaetze_neu; $i++) $text_neu[$i] = "";
		$old_abs = json_decode($this->text_neu);
		foreach ($old_abs as $abs => $str) $text_neu[$absatz_mapping[$abs]] = $str;
		$neuer_ae->setDiffParagraphs($text_neu);

		$neuer_ae->calcDiffText();


		if (!$neuer_ae->save()) var_dump($neuer_ae->attributes);

		foreach ($this->aenderungsantragUnterstuetzerInnen as $init) if ($init->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) {
			$in                      = new AenderungsantragUnterstuetzerInnen();
			$in->rolle               = IUnterstuetzerInnen::$ROLLE_INITIATORIN;
			$in->position            = $init->position;
			$in->aenderungsantrag_id = $neuer_ae->id;
			$in->unterstuetzerIn_id  = $init->unterstuetzerIn_id;
			$in->kommentar           = "";
			$in->save();
		}
	}


	/**
	 * @return array|int[]
	 */
	public function getAffectedParagraphs()
	{
		$paras = array();
		$diffs = $this->getDiffParagraphs();
		if (!is_array($diffs)) return array(); // @TODO Wie kommen fehlerhafte JSON-Strings rein? Bsp. Aenderungsantrag ID 4064
		foreach ($diffs as $i => $p) if ($p != "") $paras[] = $i;
		return $paras;
	}


	private $_firstAffectedLineOfParagraphs_relative = null;

	/**
	 * @param int $paragraph_nr
	 * @return int
	 */
	public function getFirstAffectedLineOfParagraph_relative($paragraph_nr)
	{
		if ($this->_firstAffectedLineOfParagraphs_relative === null) {
			if ($this->flp_rel_cache != "") $this->_firstAffectedLineOfParagraphs_relative = json_decode($this->flp_rel_cache, true);
			else $this->_firstAffectedLineOfParagraphs_relative = array();
		}
		if (!isset($this->_firstAffectedLineOfParagraphs_relative[$paragraph_nr])) {
			$antrag_paragraphs = $this->antrag->getParagraphsText()["bbcode"];
			$ae_diff           = $this->getDiffParagraphs();
			$diff              = DiffUtils::getTextDiffMitZeilennummern(trim($antrag_paragraphs[$paragraph_nr]), trim($ae_diff[$paragraph_nr]), $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
			$diff              = $diff->getDiff();
			$first_line        = 0;
			foreach ($diff as $diff_part) if (is_a($diff_part, "Horde_Text_Diff_Op_Copy") && $first_line == 0) {
				/** @var Horde_Text_Diff_Op_Copy $diff_part */
				$first_line = count($diff_part->orig);
			}
			$this->_firstAffectedLineOfParagraphs_relative[$paragraph_nr] = $first_line;
			$this->flp_rel_cache                                          = json_encode($this->_firstAffectedLineOfParagraphs_relative);
			$this->save(false);
		}
		return $this->_firstAffectedLineOfParagraphs_relative[$paragraph_nr];
	}

	public function flushFirstAffectedLineOfParagraphCache()
	{
		$this->_firstAffectedLineOfParagraphs_relative = null;
		$this->flp_rel_cache                           = "";
		$this->flp_abs_cache                           = "";
		$this->save(false);
	}

	private $_firstAffectedLineOfParagraphs_absolute = null;

	/**
	 * @param int $paragraph_nr
	 * @param AntragAbsatz[] $antrag_absaetze
	 * @return int
	 */
	public function getFirstAffectedLineOfParagraph_absolute($paragraph_nr, $antrag_absaetze)
	{
		if ($this->_firstAffectedLineOfParagraphs_absolute === null) {
			if ($this->flp_abs_cache != "") $this->_firstAffectedLineOfParagraphs_absolute = json_decode($this->flp_abs_cache, true);
			else $this->_firstAffectedLineOfParagraphs_absolute = array();
		}

		if (!isset($this->_firstAffectedLineOfParagraphs_absolute[$paragraph_nr])) {
			$antrag_paragraphs = $this->antrag->getParagraphsText()["bbcode"];
			$ae_diff           = $this->getDiffParagraphs();
			$diff              = DiffUtils::getTextDiffMitZeilennummern(trim($antrag_paragraphs[$paragraph_nr]), trim($ae_diff[$paragraph_nr]), $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
			$diff              = $diff->getDiff();
			$first_line        = 0;
			foreach ($diff as $diff_part) if (is_a($diff_part, "Horde_Text_Diff_Op_Copy") && $first_line == 0) {
				/** @var Horde_Text_Diff_Op_Copy $diff_part */
				$first_line = count($diff_part->orig);
			}
			$absolute_line_no = $this->antrag->getFirstLineNo();
			for ($i = 0; $i < $paragraph_nr; $i++) {
				$absolute_line_no += $antrag_absaetze[$i]->anzahl_zeilen;
			}
			$absolute_line_no += $first_line;
			$this->_firstAffectedLineOfParagraphs_absolute[$paragraph_nr] = $absolute_line_no;
			$this->flp_abs_cache                                          = json_encode($this->_firstAffectedLineOfParagraphs_absolute);
			$this->save(false);
		}
		return $this->_firstAffectedLineOfParagraphs_absolute[$paragraph_nr];
	}


	/**
	 * @param array|string[] $paragraphs
	 */
	public function setDiffParagraphs($paragraphs)
	{
		$this->text_neu                                = json_encode($paragraphs);
		$this->_firstAffectedLineOfParagraphs_relative = array();
		$this->_firstAffectedLineOfParagraphs_absolute = array();
		$this->flp_rel_cache = "";
		$this->flp_abs_cache = "";
	}

	/**
	 * @return array
	 */
	public function getDiffParagraphs()
	{
		return json_decode($this->text_neu);
	}

	/**
	 *
	 */
	public function calcDiffText()
	{
		$text_vorher = $this->antrag->text;
		$paragraphs  = $this->antrag->getParagraphs(false, false);
		$text_neu    = array();
		$diff        = $this->getDiffParagraphs();

		if (count($paragraphs) != count($diff)) throw new Exception("Inkonsistenz: Der Änderungsantrag bezieht sich womöglich auf eine frühere Version des Antrags (" . count($diff) . " Absätze vs. " . count($paragraphs) . ")");

		foreach ($paragraphs as $i => $para) {
			if ($diff[$i] != "") $text_neu[] = $diff[$i];
			else $text_neu[] = $para->str_bbcode;
		}
		$diff      = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)), $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
		$diff_text = "";

		if ($this->name_neu != $this->antrag->name) $diff_text .= "Neuer Titel des Antrags:\n[QUOTE]" . $this->name_neu . "[/QUOTE]\n\n";
		$diff_text .= DiffUtils::diff2text($diff, $this->antrag->getFirstLineNo());

		$this->aenderung_text = $diff_text;
	}


	/**
	 * @return array|AntragAbsatz[]
	 */
	public function getAntragstextParagraphs_flat()
	{
		if (!is_null($this->absaetze)) return $this->absaetze;
		$this->absaetze = array();
		$komms          = $this->aenderungsantragKommentare;

		HtmlBBcodeUtils::initZeilenCounter();
		$arr = HtmlBBcodeUtils::bbcode2html_absaetze(trim($this->aenderung_text), false, $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);

		for ($i = 0; $i < count($arr["html"]); $i++) {
			$html_plain       = HtmlBBcodeUtils::wrapWithTextClass($arr["html_plain"][$i]);
			$this->absaetze[] = new AntragAbsatz($arr["html"][$i], $html_plain, $arr["bbcode"][$i], $this->id, count($this->absaetze), $komms, array());
		}

		return $this->absaetze;
	}

	/**
	 * @return AenderungsantragAbsatz[]
	 */
	public function getAntragstextParagraphs_diff()
	{

		$abs_alt = $this->antrag->getParagraphs();
		$abs_neu = json_decode($this->text_neu);

		$this->absaetze = array();

		if (trim($this->aenderung_metatext) != "") {
			$a = HtmlBBcodeUtils::bbcode2html_absaetze($this->aenderung_metatext, true, $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);
			foreach ($a["bbcode"] as $i => $b) {
				$kommentare = array();
				foreach ($this->aenderungsantragKommentare as $komm) if ($komm->absatz == count($this->absaetze)) $kommentare[] = $komm;
				$this->absaetze[] = new AenderungsantragAbsatz($b, $b, $this->id, count($this->absaetze), null, null, $kommentare);
			}
		}

		for ($i = 0; $i < count($abs_alt); $i++) {
			$ae_absatz_nr = count($this->absaetze) + $i;
			if ($abs_neu[$i] == "") {
				$this->absaetze[] = null;
			} else {
				preg_match_all("/<span class='zeilennummer'>([0-9]+)<\/span>/siu", $abs_alt[$i]->str_html, $matches);
				$zeile_von = (isset($matches[1][0]) ? IntVal($matches[1][0]) : "????");
				$zeile_bis = (isset($matches[1]) ? $matches[1][count($matches[1]) - 1] : "???");

				$kommentare = array();
				foreach ($this->aenderungsantragKommentare as $komm) if ($komm->absatz == $ae_absatz_nr) $kommentare[] = $komm;
				$this->absaetze[] = new AenderungsantragAbsatz($abs_alt[$i]->str_bbcode, $abs_neu[$i], $this->id, $ae_absatz_nr, $zeile_von, $zeile_bis, $kommentare);
			}
		}
		return $this->absaetze;
	}

	/**
	 * @param string $revision
	 */
	public function adminFreischalten($revision = '')
	{
		if ($revision != '') {
			$this->revision_name = $revision;
		} elseif ($this->revision_name == '') {
			$this->revision_name = $this->naechsteAenderungsRevNr();
		}
		if (in_array($this->status, array(IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT, IAntrag::$STATUS_UNBESTAETIGT))) {
			$this->status = IAntrag::$STATUS_EINGEREICHT_GEPRUEFT;
		}
		$this->save();
		$this->antrag->veranstaltung->resetLineCache();

		$benachrichtigt = array();
		foreach ($this->antrag->veranstaltung->veranstaltungsreihe->veranstaltungsreihenAbos as $abo) {
			if ($abo->antraege && !in_array($abo->person_id, $benachrichtigt)) {
				$abo->person->benachrichtigenAenderungsantrag($this);
				$benachrichtigt[] = $abo->person_id;
			}
		}
	}
	/**
	 * @return int
	 */
	public function getFirstDiffLine()
	{
		if ($this->aenderung_first_line_cache > -1) return $this->aenderung_first_line_cache;

		$text_vorher = $this->antrag->text;
		$paragraphs  = $this->antrag->getParagraphs(false, false);
		$text_neu    = array();
		$diff        = $this->getDiffParagraphs();
		foreach ($paragraphs as $i => $para) {
			if (isset($diff[$i]) && $diff[$i] != "") $text_neu[] = $diff[$i];
			else $text_neu[] = $para->str_bbcode;
		}
		$diff = DiffUtils::getTextDiffMitZeilennummern(trim($text_vorher), trim(implode("\n\n", $text_neu)), $this->antrag->veranstaltung->getEinstellungen()->zeilenlaenge);

		$this->aenderung_first_line_cache = DiffUtils::getFistDiffLine($diff, $this->antrag->getFirstLineNo());
		$this->save();
		return $this->aenderung_first_line_cache;
	}

	/**
	 * @param bool $save
	 */
	public function resetLineCache($save = true)
	{
		$this->aenderung_first_line_cache = -1;
		$this->flushFirstAffectedLineOfParagraphCache();
		if ($save) $this->save();
	}

	/**
	 * @return string
	 */
	public function naechsteAenderungsRevNr()
	{
		$max_rev = 0;
		$einstellungen = $this->antrag->veranstaltung->getEinstellungen();
		if ($einstellungen->ae_nummerierung_nach_zeile) {
			$line        = $this->getFirstDiffLine();
			$ae_rev_base = $this->antrag->revision_name . "-" . $einstellungen->ae_praefix . str_pad ($line,$einstellungen->ae_ziffern,'0',STR_PAD_LEFT) . "-";
			$max_rev     = 0;
			foreach ($this->antrag->aenderungsantraege as $ae) {
				$x = explode($ae_rev_base, $ae->revision_name);
				if (count($x) == 2 && $x[1] > $max_rev) $max_rev = IntVal($x[1]);
			}
			return $ae_rev_base . ($max_rev + 1);
		} elseif ($einstellungen->ae_nummerierung_global) {
			$antraege = $this->antrag->veranstaltung->antraege;
			foreach ($antraege as $ant) {
				$m = $ant->getMaxAenderungsRevNr();
				if ($m > $max_rev) $max_rev = $m;
			}
		} else {
			$max_rev = $this->antrag->getMaxAenderungsRevNr();
		}
		return $einstellungen->ae_praefix . ($max_rev + 1);
	}

	/**
	 * @param Veranstaltung $veranstaltung
	 * @param int $limit
	 * @return array|Aenderungsantrag[]
	 */
	public static function holeNeueste($veranstaltung, $limit = 5)
	{
		$oCriteria        = new CDbCriteria();
		$oCriteria->alias = "aenderungsantrag";
		$oCriteria->addNotInCondition("aenderungsantrag.status", IAntrag::$STATI_UNSICHTBAR);
		$oCriteria->with = "antrag";
		$oCriteria->addCondition("antrag.veranstaltung_id = " . IntVal($veranstaltung->id));
		$unsichtbar = $veranstaltung->getAntragUnsichtbarStati();
		$oCriteria->addNotInCondition("antrag.status", $unsichtbar);
		$oCriteria->order = 'aenderungsantrag.datum_einreichung DESC';
		$dataProvider     = new CActiveDataProvider('Aenderungsantrag', array(
			'criteria'   => $oCriteria,
			'pagination' => array(
				'pageSize' => IntVal($limit),
			),
		));
		return $dataProvider->data;
	}

	/**
	 * @return Person[]
	 */
	public function getAntragstellerInnen()
	{
		$antragstellerInnen = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN) $antragstellerInnen[] = $relatedModel->person;
		}
		return $antragstellerInnen;
	}

    /**
     * @return string
     */
    public function getAntragstellerInnenStr() {
        $antrag = $this->getAntragstellerInnen();
        $strs = array();
        foreach ($antrag as $p) {
            $strs[] = $p->getNameMitOrga();
        }
        return implode(", ", $strs);
    }

    /**
	 * @return Person[]
	 */
	public function getUnterstuetzerInnen()
	{
		$unterstuetzerInnen = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_UNTERSTUETZERIN) $unterstuetzerInnen[] = $relatedModel->person;
		}
		return $unterstuetzerInnen;
	}

	/**
	 * @return Person[]
	 */
	public function getZustimmungen()
	{
		$zustimmung_von = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG) $zustimmung_von[] = $relatedModel->person;
		}
		return $zustimmung_von;
	}

	/**
	 * @return Person[]
	 */
	public function getAblehnungen()
	{
		$ablehnung_von = array();
		if (count($this->aenderungsantragUnterstuetzerInnen) > 0) foreach ($this->aenderungsantragUnterstuetzerInnen as $relatedModel) {
			if ($relatedModel->rolle == IUnterstuetzerInnen::$ROLLE_MAG_NICHT) $ablehnung_von[] = $relatedModel->person;
		}
		return $ablehnung_von;
	}

	/**
	 * @return bool
	 */
	public function binInitiatorIn()
	{

		if (!Yii::app()->user->isGuest) {
			/** @var Person $current_user */
			$current_user = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
			foreach ($this->aenderungsantragUnterstuetzerInnen as $u) {
				/** @var AenderungsantragUnterstuetzerInnen $u */
				if ($u->rolle == AenderungsantragUnterstuetzerInnen::$ROLLE_INITIATORIN && $u->person->id == $current_user->id) return true;
			}
		}
		return false;
	}

	/**
	 * @return int[]
	 */
	public function getMoeglicheStati()
	{
		if ($this->antrag->veranstaltung->isAdminCurUser()) {
			$stati = array();
			foreach (IAntrag::$STATI as $stat => $stat_name) {
				if ($this->status == $stat || !in_array($stat, array(IAntrag::$STATUS_ENTWURF, IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT))) $stati[] = $stat;
			}
			return $stati;
		} else {
			$meiner = false;
			foreach ($this->aenderungsantragUnterstuetzerInnen as $ant) if ($ant->rolle == IUnterstuetzerInnen::$ROLLE_INITIATORIN && $ant->person->id == $curr_person->id) $meiner = true;
			if ($meiner) {
				if ($this->status == IAntrag::$STATUS_ENTWURF) return array(IAntrag::$STATUS_ENTWURF, IAntrag::$STATUS_EINGEREICHT_UNGEPRUEFT);
				else return array($this->status);
			} else {
				return array($this->status);
			}
		}
	}

	/**
	 * @param Aenderungsantrag[] $aenderungsantraege
	 * @return Aenderungsantrag[]
	 */
	public static function sortiereSichtbareNachZeilennummer($aenderungsantraege) {
		$aes = array();
		foreach ($aenderungsantraege as $ae) if (!in_array($ae->status, IAntrag::$STATI_UNSICHTBAR)) $aes[] = $ae;

		usort($aes, function ($ae1, $ae2) {
			/** @var Aenderungsantrag $ae1 */
			/** @var Aenderungsantrag $ae2 */
			$first1 = $ae1->getFirstDiffLine();
			$first2 = $ae2->getFirstDiffLine();

			if ($first1 < $first2) return -1;
			if ($first1 > $first2) return 1;

			$x1 = explode("-", $ae1->revision_name);
			$x2 = explode("-", $ae2->revision_name);
			if (count($x1) == 3 && count($x2) == 3) {
				if ($x1[2] < $x2[2]) return -1;
				if ($x1[2] > $x2[2]) return 1;
				return 0;
			} else {
				return strcasecmp($ae1->revision_name, $ae2->revision_name);
			}
		});

		return $aes;
	}

	/**
	 * @param int $veranstaltung_id
	 * @param string $suchbegriff
	 * @return array|Aenderungsantrag[]
	 */
	public static function suche($veranstaltung_id, $suchbegriff)
	{
		$ids = array();

		/** @var array|Antrag[] $antraege */
		$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $veranstaltung_id));
		foreach ($antraege as $ant) $ids[] = $ant->id;
		if (count($ids) == 0) return array();

		return Aenderungsantrag::model()->findAll("(`aenderung_text` LIKE '%" . addslashes($suchbegriff) . "%' OR `aenderung_text2` LIKE '%" . addslashes($suchbegriff) . "%' OR `aenderung_begruendung` LIKE '%" . addslashes($suchbegriff) . "%') AND status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND antrag_id IN (" . implode(", ", $ids) . ")");
	}


	/**
	 * @param bool $absolute
	 * @return string
	 */
	public function getLink($absolute = false)
	{
		return yii::app()->getBaseUrl($absolute) . yii::app()->createUrl("aenderungsantrag/anzeige", array(
			"veranstaltungsreihe_id" => $this->antrag->veranstaltung->veranstaltungsreihe->subdomain,
			"veranstaltung_id"       => $this->antrag->veranstaltung->url_verzeichnis,
			"antrag_id"              => $this->antrag_id,
			"aenderungsantrag_id"    => $this->id
		));
	}

    /**
     * @return Veranstaltung
     */
    public function getVeranstaltung() {
        return $this->antrag->veranstaltung;
    }
}
