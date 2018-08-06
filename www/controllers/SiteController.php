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
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    protected $filterMedworkerId;
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

    public function beforeAction($action)
    {
        $this->filterMedworkerId = Yii::$app->request->cookies->getValue('FilterMedworkers');
        return parent::beforeAction($action);
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
            Yii::$app->response->cookies->remove('FilterMedworkers');
            return $this->goHome();
        }
        $odata = OData::getInstance();
        // кеширование
        $medWorkers = Yii::$app->cache->getOrSet('editEventAjax_medWorkers',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description');
        },3600*24*30);
        Yii::$app->cache->getOrSet('editEventAjax_typeEvents',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->eventTypes,'Ref_Key', 'Description');
        },3600*24*30);
        Yii::$app->cache->getOrSet('editEventAjax_clients',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->clients,'Ref_Key', 'Description');
        },3600);

        // сбрасываем кеш событий
        Yii::$app->cache->delete('eventList');

        list($begin, $end) = x_week_range(date('Y-m-d'));
        $data[$begin] = $this->getEventsForCalendar($begin, $end);
        Yii::$app->cache->set('eventList',$data, 3600);
        $resources = [];
        foreach ($medWorkers as $key=> $item) {
            if ($this->filterMedworkerId && $key != $this->filterMedworkerId) {
                continue;
            }
            $resources[] = [
                'id' => $key,
                'title' => $item,
                'eventColor' => ArrayHelper::getValue(Yii::$app->params['medWorkersColors'],$key),
            ];
        }
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
        $keyCache = $begin . $this->filterMedworkerId;
        if (!isset($data[$keyCache])) {
            $data[$keyCache] = $this->getEventsForCalendar($begin, $end);
            Yii::$app->cache->set('eventList',$data, 3600);
        }
        return [
            'events' => $data[$keyCache],
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
            $clientText = '';
            if (isset($clients[$model->clientId])) {
                $clientText = $clients[$model->clientId];
            }
            return $this->renderAjax('_editEventModal',[
                'model' => $model,
                'typeEvents' => $typeEvents,
                'medWorkers' => $medWorkers,
                'clientText' => $clientText,
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

    public function actionSaveFilterMedworkers($medworkerId = null)
    {
        if ($medworkerId) {
            $cookies = Yii::$app->response->cookies;
            $cookies->add(new Cookie([
                'name' => 'FilterMedworkers',
                'value' => $medworkerId,
            ]));
            return $this->goHome();
        }
        Yii::$app->response->cookies->remove('FilterMedworkers');
        return $this->goHome();
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
        $visits = Visit::getArrayEvents(Visit::findByDate($begin, $end, $this->filterMedworkerId));
        $emptyEvents = Event::loadFromCalendarMedWorkers($odata->eventsOnGraphic($begin, $end,$this->filterMedworkerId),$visits);
        $events = ArrayHelper::merge($emptyEvents,$visits);
        $events = ArrayHelper::toArray($events);

        return $events;
    }


}
