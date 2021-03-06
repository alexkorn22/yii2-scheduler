<?php


namespace app\models;


use Kily\Tools1C\OData\Client;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class OData extends Model
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s';
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
                Yii::$app->user->identity->loginOneC,
                Yii::$app->user->identity->passOneC
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
        $strResult = 'ОтображатьНаСайте eq true ';
        if (is_array($medWorkersId)) {

            foreach ($medWorkersId as $item) {
                $filter[] = "Ref_Key eq guid'" . $item . "'";
            }
            $strResult .= implode(' or ', $filter);

        }
        $this->client->filter($strResult);
        $data = $this->client->{'Catalog_Сотрудники'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->isClientOk($data)) {
            return [];
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function getClients()
    {
        $this->_filter = [];
        $this->_filter[] = "DeletionMark eq false";
        $this->setFilter();
        $data = $this->client->{'Catalog_Клиенты'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->isClientOk($data)) {
            return [];
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function getEventTypes()
    {
        $this->_filter = [];
        $this->_filter[] = "DeletionMark eq false";
        $this->_filter[] = "Owner_Key eq guid'" . Visit::TYPE_EVENT_VISIT . "'";
        $this->setFilter();
        $data = $this->client->{'Catalog_ВидыСобытий'}->get(null,null,['query'=>['$orderby'=>'Description asc']]);
        if(!$this->isClientOk($data)) {
            return [];
        }
        return ArrayHelper::index($data->values(), 'Ref_Key');
    }

    public function eventsOnGraphic($start, $end, $medworkerId = "")
    {
        $this->_filter = [];
        // filter date
        $this->_filter[] = "Дата ge datetime'" . date('Y-m-d\TH:i:s',strtotime($start)) . "'";
        $date = strtotime($end) + 3600 * 24;
        $this->_filter[] = "Дата lt datetime'" . date('Y-m-d\TH:i:s',$date) . "'";
        if ($medworkerId) {
            $this->_filter[] = "МедРаботник_Key eq guid'" . $medworkerId . "'";
        }
        $this->setFilter();

        $data = $this->client->{'InformationRegister_ГрафикиРаботыВрачей_RecordType'}->get();
        if(!$this->isClientOk($data)) {
            return [];
        }
        return $data->values();
    }

    // СОХРАНЕНИЕ ДАННЫХ

    public function saveVisit(Event $event)
    {
        $dataEvent = [
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
        ];
        if ($event->id) {
            $data = $this->client->{'Document_Событие'}->update($event->id,$dataEvent);
        } else {
            $dataEvent['Date'] = date(self::DATE_FORMAT);
            $dataEvent['ТипСобытия_Key'] = Visit::TYPE_EVENT_VISIT;
            $data = $this->client->{'Document_Событие'}->create($dataEvent);
        }
        if(!$this->isClientOk($data)) {
            return false;
        }
        if (!$event->id) {
            $event->id = $data->getLastId();
        }
        $data = $this->client->{'Document_Событие'}->id($event->id)->post();
        if(!$this->isClientOk($data)) {
            return false;
        }
        return true;
    }

    // OTHER

    protected function isClientOk($data = [])
    {
        if(!$this->client->isOk()) {
            $arr = $data;
            if (is_object($data)) {
                $arr = $data->toArray();
            }
            $msg =[
                'Ошибка при обращении OData: ',
                $this->client->getHttpErrorCode(),
                $this->client->getHttpErrorMessage(),
                $this->client->getErrorCode(),
                $this->client->getErrorMessage(),
                $arr,
            ];
            Yii::warning($msg,'warning_odata');
            Yii::$app->session->setFlash('error', 'Ошибка запроса к 1С');
            return false;
        }
        return true;
    }

    protected function setFilter()
    {
        $strResult = implode(' and ', $this->_filter);
        $this->client->filter($strResult);
    }

}