<?php

namespace app\controllers;

use app\models\Event;
use app\models\OData;
use app\models\Visit;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
    public function actionIndex()
    {
        list($begin, $end) = x_week_range(date('Y-m-d'));
        $odata = OData::getInstance();
        $emptyEvents = Event::loadFromCalendarMedWorkers($odata->eventsOnGraphic($begin, $end));
        $visits = Visit::getArrayEvents(Visit::findByDate($begin, $end));
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
        return $this->render('index',[
            'events' => ArrayHelper::toArray($events),
            'resources' => $resources,
        ]);
    }

    public function actionTest()
    {
        $odata = OData::getInstance();
        return $this->render('_test',[
            'events' => [],
            'medWorkers' => ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description'),
            'clients' => ArrayHelper::map($odata->clients,'Ref_Key', 'Description'),
        ]);
    }

    public function actionEditEventAjax() {
        $model = new Event();
        if (Yii::$app->request->post('action') == 'open' && Yii::$app->request->isAjax) {
            $model->load(Yii::$app->request->post(),'');
            $odata = OData::getInstance();
            return $this->renderAjax('_editEventModal',[
                'model' => $model,
                'medWorkers' => ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description'),
                'clients' => ArrayHelper::map($odata->clients,'Ref_Key', 'Description'),
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
}
