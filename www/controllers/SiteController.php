<?php

namespace app\controllers;

use app\models\Event;
use app\models\OData;
use app\models\Visit;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex($clear_cache = null)
    {
        if ($clear_cache == 'true') {
            Yii::$app->cache->flush();
        }
        $odata = OData::getInstance();
        // кеширование
        $medWorkers = Yii::$app->cache->getOrSet('editEventAjax_medWorkers',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description');
        },3600*24*30);
        $typeEvents = Yii::$app->cache->getOrSet('editEventAjax_typeEvents',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->eventTypes,'Ref_Key', 'Description');
        },3600*24*30);
        $clients =  Yii::$app->cache->getOrSet('editEventAjax_clients',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->clients,'Ref_Key', 'Description');
        },3600);


        list($begin, $end) = x_week_range(date('Y-m-d'));
        $visits = Visit::getArrayEvents(Visit::findByDate($begin, $end));
        $emptyEvents = Event::loadFromCalendarMedWorkers($odata->eventsOnGraphic($begin, $end),$visits);
        $events = ArrayHelper::merge($emptyEvents,$visits);
        $idMedWorkers = array_unique(ArrayHelper::getColumn($events,'idMedWorker'));
        $dataMedWorkers = $odata->getMedWorkers($idMedWorkers);
        $resources = [];
        foreach ($dataMedWorkers as $item) {
            $resources[] = [
                'id' => $item['Ref_Key'],
                'title' => $item['Description'],
                'eventColor' => ArrayHelper::getValue(Yii::$app->params['medWorkersColors'],$item['Ref_Key']),
            ];
        }
        $events = ArrayHelper::toArray($events);
        $data[$begin] = $events;
        $clients =  Yii::$app->cache->getOrSet('editEventAjax_clients',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->clients,'Ref_Key', 'Description');
        },3600);
        return $this->render('index',[
            'events' => [],
            'resources' => $resources,
            'currentPeriod' => ['start' => $begin,'end' => $end],
        ]);
    }

    public function actionEventList($start, $end)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        list($begin, $end) = x_week_range(date('Y-m-d',$start));
        $data = Yii::$app->cache->get('eventList');
        if (!isset($data[$begin])) {
            $data[$begin] = $this->getEventsForCalendar($begin, $end);
            Yii::$app->cache->set('eventList',$data, 3600);
        }
        return [
            'events' => $data[$begin],
            'start' => $begin,
            'end' => $end,
        ];

    }

    public function actionEditEventAjax() {
        $model = new Event();
        if (Yii::$app->request->post('action') == 'open' && Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post(),'');
            $medWorkers = Yii::$app->cache->getOrSet('editEventAjax_medWorkers',function (){
                $odata = OData::getInstance();
                return ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description');
            },3600*24*30);
            $typeEvents = Yii::$app->cache->getOrSet('editEventAjax_typeEvents',function (){
                $odata = OData::getInstance();
                return ArrayHelper::map($odata->eventTypes,'Ref_Key', 'Description');
            },3600*24*30);
            $clients =  Yii::$app->cache->getOrSet('editEventAjax_clients',function (){
                $odata = OData::getInstance();
                return ArrayHelper::map($odata->clients,'Ref_Key', 'Description');
            },3600);

            return $this->renderAjax('_editEventModal',[
                'model' => $model,
                'typeEvents' => $typeEvents,
                'medWorkers' => $medWorkers,
                'clientText' => $clients[$model->clientId],
            ]);
        }
        // POST save
        if (Yii::$app->request->post('action') == 'save') {
            $model->load(Yii::$app->request->post());
            $model->saveVisit();
            return $this->redirect(Url::home());
        }

        return new NotFoundHttpException('');
    }

    public function actionClientsList($q = null, $id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $out = ['results'];
        $clients =  Yii::$app->cache->getOrSet('editEventAjax_clients',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->clients,'Ref_Key', 'Description');
        },60*5);
        $q = trim($q);
        if ($q) {
            foreach ($clients as $key=>$client) {
                if (mb_stripos($client, $q) !== false) {
                    $out['results'][] = [
                        'id' => $key,
                        'text' => $client,
                    ];
                }
            }
        } else {
            $i = 0;
            foreach ($clients as $key=>$client) {
                $i++;
                $out['results'][] = [
                    'id' => $key,
                    'text' => $client,
                ];
                if ($i == 20) {
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    protected function getEventsForCalendar($begin, $end)
    {
        $odata = OData::getInstance();
        $visits = Visit::getArrayEvents(Visit::findByDate($begin, $end));
        $emptyEvents = Event::loadFromCalendarMedWorkers($odata->eventsOnGraphic($begin, $end),$visits);
        $events = ArrayHelper::merge($emptyEvents,$visits);
        $events = ArrayHelper::toArray($events);
        return $events;
    }

}
