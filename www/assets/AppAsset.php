<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/jquery-ui.min.css',
        'css/fullcalendar.min.css',
        'css/scheduler.min.css',
        'css/site.css',
    ];
    public $js = [
        'js/jquery-ui.min.js',
        'js/jquery.touchSwipe.min.js',
        'js/moment.min.js',
        'js/fullcalendar.min.js',
        'js/scheduler.min.js',
        'js/fullcalendar-ru.js',
        'js/main.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
