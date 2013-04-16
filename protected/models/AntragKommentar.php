<?php

/**
 * @property integer $id
 * @property integer $verfasserIn_id
 * @property integer $antrag_id
 * @property integer $absatz
 * @property string $text
 * @property string $datum
 * @property integer $status
 * @property integer $antwort_benachrichtigung
 *
 * @property Person $verfasserIn
 * @property Antrag $antrag
 * @property AntragKommentarUnterstuetzerInnen[] $unterstuetzerInnen
 */

class AntragKommentar extends IKommentar
{
    /**
     * @var string $className
     * @return GxActiveRecord
     */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string
	 */
	public function tableName() {
		return 'antrag_kommentar';
	}

	/**
	 * @param int $n
	 * @return string
	 */
	public static function label($n = 1) {
		return Yii::t('app', 'AntragKommentar|AntragKommentare', $n);
	}

	/**
	 * @return string
	 */
	public static function representingColumn() {
		return 'text';
	}

	/**
	 * @return array
	 */
	public function rules() {
		return array(
			array('text, datum', 'required'),
			array('id, verfasserIn_id, antrag_id, absatz, status, antwort_benachrichtigung', 'numerical', 'integerOnly'=>true),
			array('verfasserIn_id, antrag_id, absatz, status', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, verfasserIn_id, antrag_id, absatz, text, datum, status', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array
	 */
	public function relations() {
		return array(
			'verfasserIn' => array(self::BELONGS_TO, 'Person', 'verfasserIn_id'),
			'antrag' => array(self::BELONGS_TO, 'Antrag', 'antrag_id'),
			'unterstuetzerInnen' => array(self::HAS_MANY, 'AntragKommentarUnterstuetzerInnen', 'antrag_kommentar_id'),
		);
	}

	/**
	 * @return array
	 */
	public function pivotModels() {
		return array(
		);
	}

	/**
	 * @return array
	 */
	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'verfasserIn_id' => null,
			'antrag_id' => null,
			'absatz' => Yii::t('app', 'Absatz'),
			'text' => Yii::t('app', 'Text'),
			'datum' => Yii::t('app', 'Datum'),
			'status' => Yii::t('app', 'Status'),
			'antwort_benachrichtigung' => Yii::t('app', 'Benachrichtigung bei weiteren Antworten'),
			'verfasserIn' => null,
			'antrag' => null,
			'unterstuetzerInnen' => null,
		);
	}

	/**
	 * @return CActiveDataProvider
	 */
	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('verfasserIn_id', $this->verfasserIn_id);
		$criteria->compare('antrag_id', $this->antrag_id);
		$criteria->compare('absatz', $this->absatz);
		$criteria->compare('text', $this->text, true);
		$criteria->compare('datum', $this->datum, true);
		$criteria->compare('status', $this->status);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	/**
	 * @param int $veranstaltung_id
	 * @param int $limit
	 * @return array|AntragKommentar[]
	 */
	public static function holeNeueste($veranstaltung_id = 0, $limit = 0) {
		$condition = array(
			"order" => "datum DESC"
		);
		if ($limit > 0) $condition["limit"] = $limit;
		$arr = AntragKommentar::model()->with(array(
			"antrag" => array(
				"condition" => "antrag.status NOT IN (" . implode(", ", IAntrag::$STATI_UNSICHTBAR) . ") AND antrag.veranstaltung_id = " . IntVal($veranstaltung_id)
			),
		))->findAllByAttributes(array("status" => AntragKommentar::$STATUS_FREI), $condition);
		return $arr;
	}

	/**
	 * @return Veranstaltung
	 */
	public function getVeranstaltung()
	{
		return $this->antrag->veranstaltung;
	}

	/**
	 * @return string
	 */
	public function getAntragName()
	{
		return $this->antrag->nameMitRev();
	}

	/**
	 * @param bool $absolute
	 * @return string
	 */
	public function getLink($absolute = false)
	{
		return yii::app()->getBaseUrl($absolute) . yii::app()->createUrl("antrag/anzeige", array(
			"veranstaltungsreihe_id" => $this->antrag->veranstaltung->veranstaltungsreihe->subdomain,
			"veranstaltung_id" => $this->antrag->veranstaltung->url_verzeichnis,
			"antrag_id" => $this->antrag_id,
			"kommentar_id" => $this->id,
			"#" => "komm" . $this->id));
	}
}