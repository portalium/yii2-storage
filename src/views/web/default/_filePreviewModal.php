<?php
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Button;
use portalium\storage\Module;
use yii\helpers\Html;

Yii::$app->view->registerCss("

#filePreviewModal .modal-dialog {
  margin: 0;
  max-width: 100%;
  width: 100%;
  height: 100%;
}

#filePreviewModal .modal-content {
  background: #000000a1; 
  border: none;
  box-shadow: none;
  height: 100%;
  position: relative;
}

.modal-backdrop.show {
  opacity: 0.8; 
}

#filePreviewModal .modal-header {
  position: absolute;
  padding: 10px 15px;
  width: 100%;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 1;
  justify-content: start !important;
}

#filePreviewModal .modal-title {
  font-size: 18px;
  color: #fff;
  margin: 0;
}

#filePreviewModal .modal-header .file-icon {
  font-size: 22px;
}

#filePreviewModal .modal-header .btn-close {
  filter: invert(1);
  margin-left: auto;
}

#filePreviewContent {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

#filePreviewModal .modal-header .file-title {
  max-width: 90%;
  display: contents;  
}

.pdf-container {
  width: 90%;
  height: 90%;
  margin-top: 2%;
}

.file-preview-container,
.pdf-viewer-container,
#filePreviewContent {
  width: 100%;
  height: 100%;
}

.file-icon.word {
  color: #2b579a;
}

.file-icon.excel {
  color: #217346;
}

.file-icon.pdf {
  color: #dc3545;
}

.file-icon.powerpoint {
  color: #d24726;
}

.file-icon.video {
  color: #ea4335;
}

.file-icon.image {
  color: #34a853;
}

.file-icon.archive {
  color: #5514cc;
}

.file-icon.audio {
  color: #c809c1;
}

");

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
    <!-- Content will be loaded with JS -->
</div>

<?php Modal::end(); ?>

<?php
$js = <<<JS
window.openFilePreview = function(url, attributesRaw) {
    if (!url) return console.warn('data-url bulunamadı');

    var attributes = {};
    if (attributesRaw) {
        try { attributes = JSON.parse(attributesRaw.replace(/'/g, '"')); }
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
        content += 'onload="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="fallbackToPdfJs(\'' + url + '\', \'' + title + '\')">';
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
        content += 'onload="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="handlePreviewError(\'Resim yüklenirken hata oluştu.\')"/>';
        content += '</div>';

    } else if ([9,11,12,13].includes(parseInt(mime_type))) {
        content = '<div class="file-preview text-center">';
        content += '<video controls autoplay style="max-width:100%;max-height:70vh;" ';
        content += 'oncanplay="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="handlePreviewError(\'Video yüklenirken hata oluştu.\')">';
        content += '<source src="' + url + '" type="video/mp4">';
        content += 'Tarayıcınız video etiketini desteklemiyor.';
        content += '</video>';
        content += '</div>';

    } else {
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
}

$(document).on('dblclick', '.file-preview', function (e) {
    e.preventDefault();
    var fileItem = $(this).closest('.file-item');
    var url = fileItem.data('url');
    var attributes = fileItem.attr('data-attributes');
    openFilePreview(url, attributes);
});

function handleMultipleFilePreview(files) {
    if (!files || files.length === 0) {
        return console.warn('Önizlenecek dosya bulunamadı.');
    }

    var firstFile = files[0];
    openFilePreview(firstFile.url, firstFile.attributes);
}

JS;
$this->registerJs($js);
?>
