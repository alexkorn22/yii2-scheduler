<?php
namespace app\models;


use function GuzzleHttp\Psr7\str;
use Kily\Tools1C\OData\Client;
use function Sodium\add;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class Visit extends Model
{
    protected $odata = [];
    public $id;
    public $title;
    public $start;
    public $end;
    public $idMedWorker;
    public $clientId;
    public $typeId;
    /**
     * @var Client
     */
    protected static $client;
    public static $medWorkers;
    protected static $nameType = 'InformationRegister_События_RecordType';
    protected static $filter = [];
    const TYPE_EVENT_VISIT = '4363fb80-379e-11e6-a303-005056b6e181'; // тип события визит

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [
                [
                    'odata',
                    'start',
                    'end',
                    'id',
                    'title',
                    'idMedWorker',
                    'clientId',
                    'typeId',
                ],
                function() {return true;}
                ]
        ];

    }

    public static function findByDate($dateBegin = null,$dateEnd = null,$curMedworker = '') {
        self::initClient();
        self::setFilterByData($dateBegin, $dateEnd);
        self::$filter[] = "ТипСобытия_Key eq guid'" . self::TYPE_EVENT_VISIT. "'";
        self::setFilterByMedWorkers($curMedworker);
        self::setFilter();
        $data = self::$client->expand('Recorder,Клиент,ВидСобытия')->get(null,null,['query'=>['$orderby'=>'ДатаНачала asc']]);
        if (!self::checkOk($data)) {
            return [];
        }
        $evetsOdata = $data->values();
        $arr = self::changeArrOdata($evetsOdata);
        $visits = [];
        foreach ($arr as $item) {
            $model = new Visit();
            $model->load($item,'');
            $visits[] = $model;
        }
        return $visits;
    }

    protected static function initClient() {
        self::$filter = [];
        $client = new Client(Yii::$app->params['oDataPath'],[
            'auth' => [
                Yii::$app->user->identity->loginOneC,
                Yii::$app->user->identity->passOneC
            ],
            'timeout' => 300,
        ]);
        self::$client = $client->{self::$nameType};
    }

    protected static function setFilterByData($dateBegin,$dateEnd) {
        // begin
        self::$filter[] = "ДатаНачала ge datetime'" . date('Y-m-d\TH:i:s',strtotime($dateBegin)) . "'";
        // end
        $date = strtotime($dateEnd) + 3600 * 24;
        self::$filter[] = "ДатаНачала lt datetime'" . date('Y-m-d\TH:i:s',$date) . "'";

    }

    protected static function setFilterByMedWorkers($curMedworker)
    {
        if ($curMedworker) {
            $medWorkersId[] = $curMedworker;
        } else {
            $medWorkers = Yii::$app->cache->getOrSet('editEventAjax_medWorkers',function (){
                $odata = OData::getInstance();
                return ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description');
            },3600*24*30);
            $medWorkersId = array_keys($medWorkers);
        }
        foreach ($medWorkersId as &$item) {
            $item = "МедРаботник_Key eq guid'" . $item . "'";
        }
        $strResult = implode(' or ', $medWorkersId);
        if ($strResult != "") {
            self::$filter[] = "(" . $strResult . ")";
        }
    }

    protected static function setFilter() {
        $strResult = implode(' and ', self::$filter);
        self::$client->filter($strResult);
    }

    protected static function checkOk($data) {

        if(!self::$client->isOk()) {
            $msg =[
                'Ошибка при обращении OData: ',
                self::$client->getHttpErrorCode(),
                self::$client->getHttpErrorMessage(),
                self::$client->getErrorCode(),
                self::$client->getErrorMessage(),
                $data->toArray(),
            ];
            Yii::warning($msg,'warning_odata');
        }
        return true;
    }



    protected  static function changeArrOdata($data) {
        $result = array_map(function($data) {
            return [
                'id' => $data['Recorder_Key'],
                'start' => $data['ДатаНачала'],
                'end' => $data['ДатаОкончания'],
                'title' => self::getTitleCart($data),
                'odata' => $data,
                'idMedWorker' => $data['МедРаботник_Key'],
                'clientId' => $data['Клиент_Key'],
                'typeId' => $data['ВидСобытия_Key'],
            ];
        }, $data);
        return $result;
    }

    protected static function getTitleCart($data) {
        $res = ArrayHelper::getValue($data,'Клиент.Description') . " / " . trim(ArrayHelper::getValue($data,'Recorder.Описание'));
        $res .= " (" . ArrayHelper::getValue($data,'ВидСобытия.Description') . ")";
        return $res;
    }

    protected static function parseMedWorker($evetsOdata) {
        $result = [];
        $arr = ArrayHelper::getColumn($evetsOdata, 'МедРаботник');
        foreach ($arr as $item) {
            $result[$item['Ref_Key']] = $item;
            ArrayHelper::setValue($result, [$item['Ref_Key'], 'id'], $item['Ref_Key']);
            ArrayHelper::setValue($result, [$item['Ref_Key'], 'title'], $item['Description']);
            ArrayHelper::setValue($result, [$item['Ref_Key'], 'eventColor'], ArrayHelper::getValue(Yii::$app->params['medWorkersColors'],$item['Ref_Key']));
        }
        self::$medWorkers = $result;
    }

    public static function getArrayEvents($models) {
        $result = [];
        foreach ($models as $model) {
            $arr = ArrayHelper::toArray($model);
            ArrayHelper::setValue($arr,'resourceId',$model->idMedWorker);
            ArrayHelper::setValue($arr,'editable',false);
            ArrayHelper::setValue($arr,'description',ArrayHelper::getValue($model->odata,'Recorder.Описание'));
            $event = new Event();
            $event->load($arr,'');
            $result[] = $event;
        }
        return $result;
    }

    public static function getArrayMedWorkers() {
        $result = [];
        foreach (self::$medWorkers as $medWorker) {
            $result[] = $medWorker;
        }
        return $result;
    }

    public static function arrayForCalendarMedWorkers($visits)
    {
        $result = [];
        $visits = ArrayHelper::toArray($visits);
        foreach ($visits as $visit) {
            $day = date('Y-m-d',strtotime($visit['start']));
            $result[$day][] = $visit;
        }
        return $result;
    }

}