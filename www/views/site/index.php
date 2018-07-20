<?php
use yii\helpers\Url;
/* @var $this yii\web\View */

$this->title = 'Планировщик';
?>
<div class="site-index">
    <div id="calendar">

    </div>

    <div class="modal" id="modalEvent" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalEventLabel">Modal title</h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Save changes</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

</div>
<script>
    var resources = <?php echo json_encode($resources) ?>;
    var events = <?php echo json_encode($events)?>;
    var urlEditEvent = '<?=Url::to(['site/edit-event-ajax'])?>';
</script>
