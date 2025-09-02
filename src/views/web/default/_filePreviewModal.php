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
    var mime_type = attributes.mime_type;

    var modalHeader = '<div class="d-flex align-items-center">';
    modalHeader += '<i class="' + iconClass + ' file-icon me-2"></i>';
    modalHeader += '<span class="file-title">' + title + '</span>';
    modalHeader += '</div>';
    $('#filePreviewModal .modal-title').html(modalHeader);

    var loadingContent = '<div class="loading-spinner show text-center">';
    loadingContent += '<div class="spinner-border" role="status">';
    loadingContent += '<span class="sr-only">Yükleniyor...</span>';
    loadingContent += '</div>';
    loadingContent += '<p class="mt-2">Dosya yükleniyor...</p>';
    loadingContent += '</div>';
    $('#filePreviewContent').html(loadingContent);

    $('#filePreviewModal').modal('show');

    var content = '';

    if (mime_type == 2) {
        content = '<div class="file-preview-container">';
        content += '<div class="pdf-viewer-container">';

        content += '<embed src="' + url + '#toolbar=1&navpanes=1&scrollbar=1" ';
        content += 'type="application/pdf" class="pdf-container" ';
        content += 'onload="$(\\'#filePreviewContent .loading-spinner\\').removeClass(\\'show\\')" ';
        content += 'onerror="fallbackToPdfJs(\\'' + url + '\\', \\''
                   + title + '\\')">';
        content += '</embed>';
        content += '</div>';
        content += '</div>';

        setTimeout(function() {
            $('#filePreviewContent .loading-spinner').removeClass('show');
        }, 500);

    } else if ([0,1,17].includes(parseInt(mime_type))) {
        content = '<div class="file-preview text-center">';
        content += '<img src="' + url + '" alt="' + title + '" ';
        content += 'class="file-icon img-fluid" ';
        content += 'style="max-width:100%;max-height:70vh;" ';
        content += 'onload="$(\\'#filePreviewContent .loading-spinner\\').removeClass(\\'show\\')" ';
        content += 'onerror="handlePreviewError(\\'Resim yüklenirken hata oluştu.\\')"/>';
        content += '</div>';

    } else if ([9,11,12,13].includes(parseInt(mime_type))) {
        content = '<div class="file-preview text-center">';
        content += '<video controls autoplay style="max-width:100%;max-height:70vh;" ';
        content += 'oncanplay="$(\\'#filePreviewContent .loading-spinner\\').removeClass(\\'show\\')" ';
        content += 'onerror="handlePreviewError(\\'Video yüklenirken hata oluştu.\\')">';
        content += '<source src="' + url + '" type="video/mp4">';
        content += 'Tarayıcınız video etiketini desteklemiyor.';
        content += '</video>';
        content += '</div>';

    }else {
        content = '<div class="file-preview text-center">';
        content += '<div class="alert alert-info">';
        content += '<i class="fa fa-info-circle fa-3x mb-3"></i>';
        content += '<h5>Önizleme Desteklenmiyor</h5>';
        content += '<p>Bu dosya tipi için önizleme mevcut değil.</p>';
        content += '<a href="' + url + '" target="_blank" class="btn btn-primary">';
        content += '<i class="fa fa-download me-1"></i>Dosyayı İndir';
        content += '</a>';
        content += '</div>';
        content += '</div>';

        setTimeout(function() {
            $('#filePreviewContent .loading-spinner').removeClass('show');
        }, 100);
    }

    setTimeout(function() {
        $('#filePreviewContent').html(content);
    }, 200);
});

// PDF.js fallback
function fallbackToPdfJs(url, title) {
    console.log('PDF embed başarısız, alternatif yöntemler deneniyor...');
    var fallbackContent = '<div class="file-preview-container text-center">';
    fallbackContent += '<div class="alert alert-warning">';
    fallbackContent += '<i class="fa fa-file-pdf fa-3x mb-3 text-danger"></i>';
    fallbackContent += '<h5>PDF Önizleme</h5>';
    fallbackContent += '<p class="mb-3">PDF dosyası tarayıcıda önizlenemiyor.</p>';
    fallbackContent += '<div class="d-grid gap-2 d-md-block">';
    fallbackContent += '<button class="btn btn-primary" onclick="openPdfInNewTab(\\'' + url + '\\')">';
    fallbackContent += '<i class="fa fa-external-link-alt me-1"></i>Yeni Sekmede Aç';
    fallbackContent += '</button>';
    fallbackContent += '<a href="' + url + '" download class="btn btn-success ms-2">';
    fallbackContent += '<i class="fa fa-download me-1"></i>Dosyayı İndir';
    fallbackContent += '</a>';
    fallbackContent += '</div>';
    fallbackContent += '</div>';
    fallbackContent += '</div>';
    $('#filePreviewContent').html(fallbackContent);
}

function handlePreviewError(errorMessage) {
    var errorContent = '<div class="file-preview text-center">';
    errorContent += '<div class="alert alert-danger">';
    errorContent += '<i class="fa fa-exclamation-triangle fa-3x mb-3"></i>';
    errorContent += '<h5>Yükleme Hatası</h5>';
    errorContent += '<p>' + errorMessage + '</p>';
    errorContent += '</div>';
    errorContent += '</div>';
    $('#filePreviewContent').html(errorContent);
}

JS;
$this->registerJs($js);
?>
