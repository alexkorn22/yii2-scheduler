<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use \kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model \app\models\Event */
?>
<div class="modal" id="modalEvent" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php $form = ActiveForm::begin([
                'id' => 'editEvent',
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => '{label}<div class="col-sm-9">{input}<span class="help-block">{error}</span></div>',
                    'labelOptions' => ['class' => 'col-sm-3 control-label'],
                    'inputOptions' => ['class' => 'form-control']
                ],
            ]) ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalEventLabel">
                    <? if ($model->id):?>
                        Редактирование события
                    <? else:?>
                        Создание события
                    <? endif;?>
                </h4>
            </div>
            <div class="modal-body">
                <?= Html::hiddenInput('action','save');?>
                <?= Html::hiddenInput('Event[id]',$model->id);?>
                <?= $form->field($model, 'start')->input('datetime-local') ?>
                <?= $form->field($model, 'end')->input('datetime-local')  ?>
                <?= $form->field($model, 'typeId')->widget(Select2::classname(), [
                    'data' => $typeEvents,
                    'language' => 'ru',
                    'options' => ['placeholder' => 'Выберите вид события ...'],
                    'pluginOptions' => [
                        'allowClear' => true
                    ]
                ]); ?>
                <?= $form->field($model, 'idMedWorker')->widget(Select2::classname(), [
                        'data' => $medWorkers,
                        'language' => 'ru',
                        'options' => ['placeholder' => 'Выберите медработника ...'],
                        'pluginOptions' => [
                            'allowClear' => true
                        ]
                    ]);?>
                <?= $form->field($model, 'clientId')->widget(Select2::classname(), [
                    'initValueText' => $clientText,
                    'language' => 'ru',
                    'options' => ['placeholder' => 'Выберите клиента ...'],
                    'pluginOptions' => [
                        'allowClear' => true,
                       // 'minimumInputLength' => 3,
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['site/clients-list']),
                            'dataType' => 'json',
                            'delay' => 600,
                            'data' => new \yii\web\JsExpression('function(params) { return {q:params.term}; }')
                        ],
                    ]
                ]); ?>
                <?= $form->field($model, 'description')->textarea() ?>

            </div>
            <div class="modal-footer">
                <?= Html::submitButton('Сохранить',['class'=> 'btn btn-primary'])?>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>