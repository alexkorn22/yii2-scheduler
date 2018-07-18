<?php
namespace app\models;


use function GuzzleHttp\Psr7\str;
use Kily\Tools1C\OData\Client;
use Yii;
use yii\base\Model;

class Event extends Model
{
    /**
     * @var Client
     */
    protected static $client;
    protected static $nameType = 'InformationRegister_События_RecordType';

    public static function findByDate($dateBegin = null,$dateEnd = null) {
        self::initClient();
        self::setFilterByData($dateBegin, $dateEnd);
        $data = self::$client->get(null,null,['query'=>['$orderby'=>'ДатаНачала asc']]);
        if(!self::$client->isOk()) {
            var_dump('Something went wrong: ',self::$client->getHttpErrorCode(),self::$client->getHttpErrorMessage(),self::$client->getErrorCode(),self::$client->getErrorMessage(),$data->toArray());
            die();
        }
        $evetsOdata = $data->values();
        return $evetsOdata;
    }

    protected static function initClient() {
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
        $arrConditions = [];
        // begin
        $arrConditions[] = "ДатаНачала ge datetime'" . date('Y-m-d\TH:i:s',strtotime($dateBegin)) . "'";
        // end
        $date = strtotime($dateEnd) + 3600 * 24;
        $arrConditions[] = "ДатаНачала lt datetime'" . date('Y-m-d\TH:i:s',$date) . "'";
        $strResult = implode(' and ', $arrConditions);
        //dd($strResult);
        self::$client->filter($strResult);

    }


}