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
    /**
     * @var Client
     */
    protected static $client;
    public static $medWorkers;
    protected static $nameType = 'InformationRegister_События_RecordType';
    protected static $filter = [];
    const TYPE_EVENT_VISIT = '03e07ea8-4441-11e6-98ba-005056b6e181';

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
                ],
                function() {return true;}
                ]
        ];

    }

    public static function findByDate($dateBegin = null,$dateEnd = null) {
        self::initClient();
        self::setFilterByData($dateBegin, $dateEnd);
        self::$filter[] = "ВидСобытия_Key eq guid'" . self::TYPE_EVENT_VISIT. "'";
        self::setFilter();
        $data = self::$client->expand('МедРаботник,Recorder,Клиент')->get(null,null,['query'=>['$orderby'=>'ДатаНачала asc']]);
        if (!self::checkOk($data)) {
            return [];
        }
        $evetsOdata = $data->values();
        self::parseMedWorker($evetsOdata);

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
                Yii::$app->params['authLogin'],
                Yii::$app->params['authPass']
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

    protected static function setFilter() {
        $strResult = implode(' and ', self::$filter);
        self::$client->filter($strResult);
    }

    protected static function checkOk($data) {
        if(!self::$client->isOk()) {
            var_dump('Something went wrong: ',self::$client->getHttpErrorCode(),self::$client->getHttpErrorMessage(),self::$client->getErrorCode(),self::$client->getErrorMessage(),$data->toArray());
            return false;
        }
        return true;
    }

    protected  static function changeArrOdata($data) {
        $result = array_map(function($data) {
            return [
                'id' => $data['Recorder_Key'],
                'start' => $data['ДатаНачала'],
                'end' => $data['ДатаОкончания'],
                'title' => ArrayHelper::getValue($data,'Клиент.Description'),
                'odata' => $data,
                'idMedWorker' => $data['МедРаботник_Key'],
                'clientId' => $data['Клиент_Key'],
            ];
        }, $data);
        return $result;
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
            ArrayHelper::setValue($arr,'editable',true);
            ArrayHelper::setValue($arr,'description',ArrayHelper::getValue($model->odata,'Recorder.Описание'));
            $result[] = $arr;
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

}