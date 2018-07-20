<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model \app\models\EventForm */
?>
<div class="modal" id="modalEvent" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php $form = ActiveForm::begin([
                'id' => 'editEvent',
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => '{label}<div class="col-sm-9">{input}</div>',
                    'labelOptions' => ['class' => 'col-sm-3 control-label'],
                    'inputOptions' => ['class' => 'form-control']
                ],
            ]) ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalEventLabel">Modal title</h4>
            </div>
            <div class="modal-body">
                <?= $form->field($model, 'start')->textInput() ?>
                <?= $form->field($model, 'end')->textInput() ?>
                <?= $form->field($model, 'idMedWorker')->textInput() ?>
                <?= $form->field($model, 'clientId')->textInput() ?>
                <?= $form->field($model, 'description')->textarea() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Save changes</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>