<?php

/**
 * @property integer $id
 * @property integer $verfasserIn_id
 * @property integer $aenderungsantrag_id
 * @property integer $absatz
 * @property string $text
 * @property string $datum
 * @property integer $status
 * @property integer $antwort_benachrichtigung
 *
 * @property Aenderungsantrag $aenderungsantrag
 * @property Person $verfasserIn
 */
class AenderungsantragKommentar extends IKommentar
{
	/**
	 * @var $clasName string
	 * @return GxActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}


	public function tableName()
	{
		return 'aenderungsantrag_kommentar';
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'AenderungsantragKommentar|AenderungsantragKommentare', $n);
	}

	public static function representingColumn()
	{
		return 'datum';
	}

	public function rules()
	{
		return array(
			array('text, datum', 'required'),
			array('id, verfasserIn_id, aenderungsantrag_id, absatz, status, antwort_benachrichtigung', 'numerical', 'integerOnly' => true),
			array('verfasserIn_id, aenderungsantrag_id, absatz, text, status', 'default', 'setOnEmpty' => true, 'value' => null),
		);
	}

	public function relations()
	{
		return array(
			'aenderungsantrag' => array(self::BELONGS_TO, 'Aenderungsantrag', 'aenderungsantrag_id'),
			'verfasserIn'      => array(self::BELONGS_TO, 'Person', 'verfasserIn_id'),
		);
	}

	public function pivotModels()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id'                       => Yii::t('app', 'ID'),
			'verfasserIn_id'           => null,
			'aenderungsantrag_id'      => null,
			'absatz'                   => Yii::t('app', 'Absatz'),
			'text'                     => Yii::t('app', 'Text'),
			'datum'                    => Yii::t('app', 'Datum'),
			'status'                   => Yii::t('app', 'Status'),
			'antwort_benachrichtigung' => Yii::t('app', 'Benachrichtigung bei weiteren Antworten'),
			'aenderungsantrag'         => null,
			'verfasserIn'              => null,
		);
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->aenderungsantrag->antrag->veranstaltung;
	}


	/**
	 * @param Veranstaltung $veranstaltung
	 * @param int $limit
	 * @return array|AenderungsantragKommentar[]
	 */
	public static function holeNeueste($veranstaltung, $limit = 0)
	{
		$antrag_ids = array();
		/** @var array|Antrag[] $antraege */
		$antraege = Antrag::model()->findAllByAttributes(array("veranstaltung_id" => $veranstaltung->id));
		foreach ($antraege as $a) $antrag_ids[] = $a->id;

		if (count($antrag_ids) == 0) return array();

		$condition = array(
			"order" => "datum DESC"
		);
		if ($limit > 0) $condition["limit"] = $limit;
		$unsichtbar = $veranstaltung->getAntragUnsichtbarStati();
		$arr = AenderungsantragKommentar::model()->with(array(
			"aenderungsantrag" => array(
				"condition" => "aenderungsantrag.status NOT IN (" . implode(", ", $unsichtbar) . ") AND aenderungsantrag.antrag_id IN (" . implode(", ", $antrag_ids) . ")"
			),
		))->findAllByAttributes(array("status" => AenderungsantragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}


	public function getAntragName()
	{
		return $this->aenderungsantrag->revision_name . " zu " . $this->aenderungsantrag->antrag->nameMitRev();
	}

	/**
	 * @param bool $absolute
	 * @return string
	 */
	public function getLink($absolute = false)
	{
		return yii::app()->getBaseUrl($absolute) . yii::app()->createUrl("aenderungsantrag/anzeige", array(
			"veranstaltungsreihe_id" => $this->aenderungsantrag->antrag->veranstaltung->veranstaltungsreihe->subdomain,
			"veranstaltung_id" => $this->aenderungsantrag->antrag->veranstaltung->url_verzeichnis,
			"aenderungsantrag_id" => $this->aenderungsantrag_id,
			"antrag_id" => $this->aenderungsantrag->antrag_id,
			"kommentar_id" => $this->id, "#" => "komm" . $this->id));
	}
}