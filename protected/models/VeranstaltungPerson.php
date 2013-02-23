<?php

/**
 * This is the model base class for the table "antrag_unterstuetzer".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "AntragUnterstuetzer".
 *
 * Columns in table "antrag_unterstuetzer" available as properties of the model,
 * followed by relations of table "antrag_unterstuetzer" available as properties of the model.
 *
 * @property integer $veranstaltung_id
 * @property integer $person_id
 * @property string $rolle
 *
 * @property Veranstaltung $veranstaltung
 * @property Person $person
 */
class VeranstaltungPerson extends GxActiveRecord {

	public static $STATUS_ADMIN = "admin";
	public static $STATUS_DABEI = "dabei";
	public static $STATUS_ABO = "abo";
	public static $STATUS_DELEGIERT = "delegiert";
	public static $STATUS = array(
		"delegiert" => "Delegiert",
		"admin" => "Admin",
		"abo" => "Abonniert",
		"dabei" => "Dabei",
	);

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'veranstaltung_person';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'VeranstaltungPerson|VeranstaltungPersonen', $n);
	}

	public static function representingColumn() {
		return 'rolle';
	}

	public function rules() {
		return array(
			array('veranstaltung_id, person_id, rolle', 'required'),
			array('veranstaltung_id, person_id', 'numerical', 'integerOnly'=>true),
			array('rolle', 'length', 'max'=>12),
			array('veranstaltung_id, person_id, rolle', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'veranstaltung' => array(self::BELONGS_TO, 'Veranstaltung', 'veranstaltung_id'),
			'person' => array(self::BELONGS_TO, 'Person', 'person_id'),
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'veranstaltung_id' => null,
			'person_id' => null,
			'rolle' => Yii::t('app', 'Rolle'),
			'veranstaltung' => null,
			'person' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('veranstaltung_id', $this->veranstaltung_id);
		$criteria->compare('person_id', $this->person_id);
		$criteria->compare('rolle', $this->rolle, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}