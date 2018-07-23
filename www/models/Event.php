<?php

namespace app\models;

use yii\base\Model;
use yii\helpers\ArrayHelper;

class Event extends Model
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s';
    public $id;
    public $start;
    public $end;
    public $idMedWorker;
    public $clientId;
    public $description;
    public $title;
    public $editable = true;
    public $resourceId; // вспомагательное поле

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['start', 'end', 'idMedWorker', 'clientId', 'description', 'title', 'id','resourceId'], function() {
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

    public static function loadFromCalendarMedWorkers(array $data)
    {
        $models = [];
        foreach ($data as $item) {
            $curTime = strtotime($item['ВремяНачала']) - strtotime('0001-01-01');
            $endDay = strtotime($item['ВремяОкончания']) - strtotime('0001-01-01');
            while ($curTime < $endDay) {
                $model = new self();
                $model->start = date(self::DATE_FORMAT,strtotime($item['Дата'])  + $curTime);
                $curTime += $item['РегулярностьПриема'] * 60;
                $model->end = date(self::DATE_FORMAT,strtotime($item['Дата'])  + $curTime);
                $model->idMedWorker = $item['МедРаботник_Key'];
                $model->resourceId = $item['МедРаботник_Key'];
                $models[] = $model;
            }
        }
        return $models;
    }


    public function saveVisit()
    {
        $odata = OData::getInstance();
        $odata->saveVisit($this);
        return true;
    }

}