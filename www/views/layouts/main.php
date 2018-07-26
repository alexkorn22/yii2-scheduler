<?php

/* @var $this \yii\web\View */

/* @var $content string */

use app\widgets\Alert;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse',
        ],
    ]);
    $items = [];
    $items[] = ['label' => 'Home', 'url' => ['/site/index']];
    $items[] = ['label' => 'Обновить данные', 'url' => \yii\helpers\Url::to(['site/index', 'clear_cache' => 'true'])];
    $items[] = Yii::$app->user->isGuest ? (
    ['label' => 'Login', 'url' => ['/site/login']]
    ) : (
        '<li>'
        . Html::beginForm(['/site/logout'], 'post')
        . Html::submitButton(
            'Logout (' . Yii::$app->user->identity->username . ')',
            ['class' => 'btn btn-link logout']
        )
        . Html::endForm()
        . '</li>'
    );
    echo \app\widgets\FilterMedworker::widget();
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => $items,
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; АСС <?= date('Y') ?></p>
    </div>
</footer>
<div id="modalContainer">

</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
