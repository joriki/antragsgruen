<?php

/**
 * @property integer $id
 * @property string $an_email
 * @property integer $an_person
 * @property integer $typ
 * @property string $von_email
 * @property string $datum
 * @property string $betreff
 * @property string $text
 *
 * @property Person $an_person_obj
 */
class EmailLog extends GxActiveRecord
{

	public static $EMAIL_TYP_SONSTIGES = 0;
	public static $EMAIL_TYP_REGISTRIERUNG = 1;
	public static $EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_USER = 2;
	public static $EMAIL_TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN = 3;
	public static $EMAIL_TYP_NAMESPACED_ACCOUNT_ANGELEGT = 4;
	public static $EMAIL_TYP_DEBUG = 5;
	public static $EMAIL_TYP_TAGS = array(
		0 => "Sonstiges",
		1 => "Registrierung",
		2 => "Benachrichtigung User",
		3 => "Benachrichtigung Admin",
		4 => "Namespaced_Angelegt",
		5 => "Debug",
	);

	/**
	 * @param string $className
	 * @return EmailLog
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public static function label($n = 1)
	{
		return Yii::t('app', 'E-Mail-Log|E-Mail-Logs', $n);
	}


	public function tableName()
	{
		return 'email_log';
	}

	public static function representingColumn()
	{
		return 'id';
	}

	public function rules()
	{
		return array(
			array('an_email, von_email, datum, betreff, text', 'required'),
			array('id, an_person, typ', 'numerical', 'integerOnly' => true),
			array('an_email, von_email, betreff', 'length', 'max' => 200),
			array('text', 'safe'),
		);
	}

	public function relations()
	{
		return array(
			'an_person_obj' => array(self::BELONGS_TO, 'Person', 'an_person'),
		);
	}

	public function pivotModels()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id'            => 'ID',
			'an_email'      => 'An E-Mail',
			'an_person'     => 'An Person ID',
			'an_person_obj' => 'An Person',
			'typ'           => 'Typ',
			'von_email'     => 'Von E-Mail',
			'datum'         => 'Datum',
			'betreff'       => 'Betreff',
			'text'          => 'Text',
		);
	}
}