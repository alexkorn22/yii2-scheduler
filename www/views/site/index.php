<?php
use yii\helpers\Url;
/* @var $this yii\web\View */

$this->title = 'Планировщик';
?>
<div class="site-index">
    <div id="calendar">

    </div>
</div>

<script>
    var resources = <?php echo json_encode($resources) ?>;
    var events = <?php echo json_encode($events)?>;
    var urlEditEvent = '<?=Url::to(['site/edit-event-ajax'])?>';
    var currentPeriod = <?php echo json_encode($currentPeriod)?>;
    var curStart = new Date('1920-12-01');
    var curEnd = new Date('1920-12-01');

</script>
