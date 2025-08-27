<?php
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Button;
use portalium\storage\Module;
use yii\helpers\Html;

Yii::$app->view->registerCss("



");

/* Modal oluşturma */
Modal::begin([
    'id' => 'filePreviewModal',
    'title' => '',
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body text-center'],
    'clientOptions' => [
        'backdrop' => 'static',
        'keyboard' => true,
    ],
    'dialogOptions' => ['class' => 'modal-dialog-centered modal-lg']
]);
?>

<div id="filePreviewContent">
    <!-- İçerik JS ile yüklenecek -->
</div>

<?php Modal::end(); ?>

<?php
$js = <<<JS
$(document).on('dblclick', '.file-preview', function (e) {
    e.preventDefault();
    var \$fileItem = $(this).closest('.file-item');
    var url = \$fileItem.data('url');
    if (!url) return console.warn('data-url bulunamadı');

    var rawAttributes = \$fileItem.attr('data-attributes');
    var attributes = {};
    if (rawAttributes) {
        try { attributes = JSON.parse(rawAttributes.replace(/'/g, '"')); }
        catch (err) { console.warn('data-attributes parse edilemedi', err); }
    }

    var title = attributes.title || 'Başlık yok';
    var iconClass = attributes.icon_class_php || 'fa fa-file'; 

    var modalHeader = '<div class="d-flex align-items-center">';
    modalHeader += '<i class="' + iconClass + ' file-icon me-2"></i>';
    modalHeader += '<span class="file-title">' + title + '</span>';
    modalHeader += '</div>';
    $('#filePreviewModal .modal-title').html(modalHeader);

    var content = '<div class="file-preview text-center">';
    content += '<img src="' + url + '" alt="' + title + '" class="file-icon img-fluid" style="max-width:100%;max-height:70vh;" />';
    content += '</div>';
    $('#filePreviewContent').html(content);

    $('#filePreviewModal').modal('show');
});


JS;
$this->registerJs($js);
?>
