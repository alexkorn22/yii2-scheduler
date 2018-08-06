<?php
/**
 * Created by PhpStorm.
 * User: korns
 * Date: 26.07.2018
 * Time: 13:07
 */

namespace app\widgets;


use app\models\OData;
use kartik\select2\Select2;
use Yii;
use yii\base\DynamicModel;
use yii\bootstrap\Nav;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

class FilterMedworker extends \yii\bootstrap\Widget
{
    protected $model;

    public function init() {
        parent::init();
    }

    public function run() {
        if (Yii::$app->user->isGuest) {
            return '';
        }
        $model = new DynamicModel(['medworkerId']);
        $model->medworkerId = Yii::$app->request->cookies->getValue('FilterMedworkers');
        $output = Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-left navbar-medworkers'],
            'items' => [
                ['label' => 'Медработник'],
                '<li class="li-select">' . Select2::widget([
                    'model' => $model,
                    'attribute' => 'medworkerId',
                    'data' => $this->listMedworkers(),
                    'options' => ['placeholder' => 'Показывать всех ...'],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                    'pluginEvents' => [
                        "select2:select" => "function(e) { selectFilterMedworkers(e.params.data.id); }",
                        "select2:unselect" => "function(e) { selectFilterMedworkers(); }",
                    ]
                ]) . '</li>',
            ],
        ]);
        return $output;
    }

    protected function listMedworkers()
    {
        return Yii::$app->cache->getOrSet('editEventAjax_medWorkers',function (){
            $odata = OData::getInstance();
            return ArrayHelper::map($odata->medWorkers,'Ref_Key', 'Description');
        },3600*24*30);
    }

}