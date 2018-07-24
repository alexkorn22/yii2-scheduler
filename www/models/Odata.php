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
    protected $_filter;

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

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ПОЛУЧЕНИЕ ДАННЫХ

    public function getMedWorkers($medWorkersId = null)
    {
        $this->_filter = [];
        if (is_array($medWorkersId)) {
            $filter = [];
            foreach ($medWorkersId as $item) {
                $filter[] = "Ref_Key eq guid'" . $item . "'";
            }
            $strResult = implode(' or ', $filter);
            $this->client->filter($strResult);
        }
        $data = $this->client->{'Catalog_Сотрудники'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function getClients()
    {
        $data = $this->client->{'Catalog_Клиенты'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function getEventTypes()
    {
        $data = $this->client->{'Catalog_ВидыСобытий'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function eventsOnGraphic($start, $end)
    {
        $this->_filter = [];
        // filter date
        $this->_filter[] = "Дата ge datetime'" . date('Y-m-d\TH:i:s',strtotime($start)) . "'";
        $date = strtotime($end) + 3600 * 24;
        $this->_filter[] = "Дата lt datetime'" . date('Y-m-d\TH:i:s',$date) . "'";
        $this->setFilter();

        $data = $this->client->{'InformationRegister_ГрафикиРаботыВрачей_RecordType'}->get();
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
        return $data->values();
    }
    
    // СОХРАНЕНИЕ ДАННЫХ

    public function saveVisit(Event $event)
    {
        $data = $this->client->{'Catalog_Клиенты'}->get($event->clientId,null,['query'=>['$orderby'=>'Description asc']])->values();
        //dd($data);
        $data = $this->client->{'Document_Событие'}->update($event->id,[
            'Описание'=> $event->description,
            'ДатаНачала'=> date('Y-m-d\TH:i:s',strtotime($event->start)),
            'ДатаОкончания'=> date('Y-m-d\TH:i:s', strtotime($event->end)),
            'МедРаботник_Key' => $event->idMedWorker,
            'ВидСобытия_Key' => $event->typeId,
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
        $data = $this->client->{'Document_Событие'}->id($event->id)->post();
        if(!$this->client->isOk()) {
            var_dump('Something went wrong: ',$this->client->getHttpErrorCode(),$this->client->getHttpErrorMessage(),$this->client->getErrorCode(),$this->client->getErrorMessage(),$data->toArray());
            die();
        }
    }

    // OTHER

    protected function setFilter()
    {
        $strResult = implode(' and ', $this->_filter);
        $this->client->filter($strResult);
    }

}