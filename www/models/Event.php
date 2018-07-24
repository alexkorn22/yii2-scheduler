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
    public $typeId;
    public $resourceId; // вспомагательное поле

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['start', 'end', 'idMedWorker', 'clientId', 'description', 'title', 'id','resourceId','typeId'], function() {
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
            'typeId' => 'Тип визита',
        ];
        ArrayHelper::merge(parent::attributeLabels(),$labels);
        return $labels;
    }

    public static function loadFromCalendarMedWorkers(array $data, $visits)
    {
        $models = [];
        $visits = Visit::arrayForCalendarMedWorkers($visits);

        foreach ($data as $item) {
            $curTime = strtotime($item['ВремяНачала']) - strtotime('0001-01-01');
            $endDay = strtotime($item['ВремяОкончания']) - strtotime('0001-01-01');
            $day = date('Y-m-d',strtotime($item['Дата']));
            $dayVisits = ArrayHelper::getValue($visits,$day,[]);

            while ($curTime < $endDay) {

                $startTime = strtotime($item['Дата'])  + $curTime;
                $curTime += $item['РегулярностьПриема'] * 60;
                $endTime = strtotime($item['Дата']) + $curTime;

                foreach ($dayVisits as $dayVisit) {
                    if ($dayVisit['idMedWorker'] != $item['МедРаботник_Key'] ) {
                        continue;
                    }
                    $startDayVisit = strtotime($dayVisit['start']);
                    $endDayVisit = strtotime($dayVisit['end']);
                    if ($startTime >= $startDayVisit && $startTime < $endDayVisit) {
                        $startTime = $endDayVisit;
                    }
                    if ($endTime > $startDayVisit && $endTime <= $endDayVisit) {
                        $endTime = $startDayVisit;
                    }
                }

                if ($startTime >= $endTime) {
                    continue;
                }
                $model = new self();
                $model->start = date(Event::DATE_FORMAT, $startTime);
                $model->end = date(self::DATE_FORMAT,$endTime);
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