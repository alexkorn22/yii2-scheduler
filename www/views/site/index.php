<?php

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
</script>
