<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\ArrayHelper;

class Event extends Model
{

    public $eventId;
    public $start;
    public $end;
    public $idMedWorker;
    public $clientId;
    public $description;
    public $title;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['start', 'end', 'idMedWorker', 'clientId', 'description', 'title', 'eventId'], function() {
            return true;
            }],
        ];
    }

    public function attributeLabels()
    {
        $labels = [
            'start' => 'Дата начала',
            'end' => 'Дата окончания',
            'idMedWorker' => 'Медработник',
            'clientId' => 'Клиент',
            'description' => 'Описание',
        ];
        ArrayHelper::merge(parent::attributeLabels(),$labels);
        return $labels;
    }

    public function saveVisit()
    {
        $odata = OData::getInstance();
        $odata->saveVisit($this);
        return true;
    }

}