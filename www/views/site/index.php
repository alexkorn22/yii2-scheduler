<?php

/* @var $this yii\web\View */

$this->title = 'Планировщик';

?>
<div class="site-index">
    <div id="calendar">

    </div>
</div>
<script>
    var NUM_COLUMNS = <?php echo $countColumns ?>;
    var columnHeaders = <?php echo json_encode($columnHeaders) ?>;
    var events = <?php echo $events ?>;
</script>
