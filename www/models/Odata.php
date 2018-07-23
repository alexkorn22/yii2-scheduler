<?php


namespace app\models;


use Kily\Tools1C\OData\Client;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class OData extends Model
{
    protected static $instance;
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client(Yii::$app->params['oDataPath'],[
            'auth' => [
                Yii::$app->params['authLogin'],
                Yii::$app->params['authPass']
            ],
            'timeout' => 300,
        ]);
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMedWorkers() {
        $data = $this->client->{'Catalog_Сотрудники'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function getClients() {
        $data = $this->client->{'Catalog_Клиенты'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function saveVisit(Event $event)
    {
        $data = $this->client->{'Catalog_Клиенты'}->get($event->clientId,null,['query'=>['$orderby'=>'Description asc']])->values();
        //dd($data);
        $data = $this->client->{'Document_Событие'}->update($event->eventId,[
            'Описание'=> $event->description,
            'ДатаНачала'=> date('Y-m-d\TH:i:s',strtotime($event->start)),
            'ДатаОкончания'=> date('Y-m-d\TH:i:s', strtotime($event->end)),
            'МедРаботник_Key' => $event->idMedWorker,
            'Участники' => [
                [
                    'LineNumber' => '1',
                    'Контакт_Key' => $event->clientId,
                    'Выполнено' => false,
                ]
            ]
        ]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        $data = $this->client->{'Document_Событие'}->id($event->eventId)->post();
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
    }

}