<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var $model portalium\storage\models\Storage */
/* @var $dataProvider yii\data\ActiveDataProvider */

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
echo Html::beginTag('span', ['class' => 'col-md-4 d-flex gap-2']);
echo Html::tag(
    'span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'id' => 'searchFileInput',
        'placeholder' => Module::t('Search file..')
    ]) .
        Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), ['class' => 'input-group-text']),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload'), ['class' => 'ms-2']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'onclick' => 'openUploadModal()',
    ],
]);

echo Html::endTag('span');
echo html::tag('br');

Pjax::begin([
    'id' => 'upload-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'list-file-pjax'
]);
echo $this->render('_file-list', [
    'dataProvider' => $dataProvider,
    'isPicker' => $isPicker ?? false
]);
Pjax::end();

Pjax::begin([
    'id' => 'rename-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);


Pjax::end();

Pjax::begin([
    'id' => 'update-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);

Pjax::end();

Pjax::begin([
    'id' => 'share-file-pjax',
]);
Pjax::end();

Pjax::begin([
    'id' => 'search-file-pjax',
]);
Pjax::end();

?>
<?php

$this->registerJs(
    <<<JS

    function refreshFileList() {
        if ($('#file-picker-modal').length) {
            $.ajax({
                url: '/storage/default/picker-modal',
                type: 'GET',
                success: function (data) {
                    $('#file-picker-modal .modal-content').html($(data).find('.modal-content').html());
                },
                error: function () {
                    alert('Modal listesi güncellenemedi.');
                }
            });
        } else {
            $.pjax.reload({container: '#list-file-pjax'});
        }
    }

   
    function openRenameModal(id) {
        $.ajax({
            url: '/storage/default/rename-file',
            type: 'GET',
            data: { id: id },
            success: function (data) {
                $('#renameModal').remove(); 
                $('body').append(data); 
                const modalRename = new bootstrap.Modal(document.getElementById('renameModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modalRename.show();
            }
        });
    }


    function openUpdateModal(id) {
        $.ajax({
            url: '/storage/default/update-file',
            type: 'GET',
            data: { id: id },
            success: function (data) {
                $('#updateModal').remove();
                $('body').append(data);
                const modalUpdate = new bootstrap.Modal(document.getElementById('updateModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modalUpdate.show();
            }
        });
    }

  
    function openShareModal(id) {
        $.ajax({
            url: '/storage/default/share-file',
            type: 'GET',
            data: { id: id },
            success: function (data) {
                $('#shareModal').remove();
                $('body').append(data);
                const modalShare = new bootstrap.Modal(document.getElementById('shareModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modalShare.show();
            }
        });
    }


    function openUploadModal() {
        $.ajax({
            url: '/storage/default/upload-file',
            type: 'GET',
            success: function (data) {
                $('#uploadModal').remove();
                $('body').append(data);
                const modalUpload = new bootstrap.Modal(document.getElementById('uploadModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modalUpload.show();
            }
        });
    }

 
    function copyFile(id) {
        event.preventDefault();
        $.ajax({
            url: '/storage/default/copy-file',
            type: 'POST',
            data: { id: id },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                refreshFileList(); 
                rebindSearchEvent(); 
            },
            error: function () {
                alert('Error copying file!');
            }
        });
    }


    function deleteFile(id) {
        event.preventDefault();
        $.ajax({
            url: '/storage/default/delete-file',
            type: 'POST',
            data: { id: id },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                refreshFileList(); 
                rebindSearchEvent(); 
            },
            error: function () {
                alert('Error deleting file!');
            }
        });
    }

   
    function downloadFile(id) {
        event.preventDefault();
        $.ajax({
            url: '/storage/default/download-file',
            type: 'POST',
            data: { id: id },
            xhrFields: { responseType: 'blob' },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data, status, xhr) {
                const disposition = xhr.getResponseHeader('Content-Disposition');
                const filename = disposition && disposition.indexOf('filename=') !== -1
                    ? disposition.split('filename=')[1].replace(/['"]+/g, '')
                    : 'file.bin';
                const blob = new Blob([data]);
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = decodeURIComponent(filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                refreshFileList(); 
                rebindSearchEvent(); 
            },
            error: function () {
                alert('Error downloading file!');
            }
        });
    }

   
    function rebindSearchEvent() {
        $('#searchFileInput').off('keyup').on('keyup', function () {
            const q = $(this).val().trim();
            const fileExtensions = Array.isArray(window.fileExtensions) ? window.fileExtensions.join(',') : '';
            const isPicker = window.isPicker ? 1 : 0;
            let baseUrl = '/storage/default/search?q=' + encodeURIComponent(q);
            if (fileExtensions) {
                baseUrl += '&fileExtensions=' + encodeURIComponent(fileExtensions);
            }
            baseUrl += '&isPicker=' + isPicker;
            $.pjax.reload({
                container: '#list-file-pjax',
                url: baseUrl,
                timeout: false
            });
        });

        
        $('#searchFileInput').trigger('keyup');
    }

   
    $(document).off('click.rename').on('click.rename', '#renameButton', function(e) {
        e.preventDefault();
        const form = $('#renameForm');
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('renameModal')).hide();
                refreshFileList();
                rebindSearchEvent(); 
            }
        });
    });

    $(document).off('click.update').on('click.update', '#updateButton', function(e) {
        e.preventDefault();
        const form = document.getElementById('updateForm');
        const formData = new FormData(form);
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('updateModal')).hide();
                refreshFileList();
                rebindSearchEvent(); 
            },
            error: function (xhr, status, error) {
                alert('An error occurred while updating the file. Please try again.');
                console.error('Error:', error);
            }
        });
    });

    $(document).off('click.share').on('click.share', '#shareButton', function(e) {
        e.preventDefault();
        const form = $('#shareForm');
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
                refreshFileList();
                rebindSearchEvent();
            }
        });
    });

  
    $(document).off('click.copy').on('click.copy', '.file-card .fa-copy', function(e) {
        e.preventDefault();
        const id = $(this).closest('.file-card').data('id');
        copyFile(id);
    });

    $(document).off('click.remove').on('click.remove', '.file-card .fa-trash', function(e) {
        e.preventDefault();
        const id = $(this).closest('.file-card').data('id');
        deleteFile(id);
    });

    $(document).off('click.download').on('click.download', '.file-card .fa-download', function(e) {
        e.preventDefault();
        const id = $(this).closest('.file-card').data('id');
        downloadFile(id);
    });

    $(document).ready(function () {
        let searchTimer;
        $('#searchFileInput').on('keyup', function () {
            clearTimeout(searchTimer);
            const q = $(this).val().trim();
            const fileExtensions = Array.isArray(window.fileExtensions) ? window.fileExtensions.join(',') : '';
            const isPicker = window.isPicker ? 1 : 0;
            let baseUrl = '/storage/default/search?q=' + encodeURIComponent(q);
            if (fileExtensions) {
                baseUrl += '&fileExtensions=' + encodeURIComponent(fileExtensions);
            }
            baseUrl += '&isPicker=' + isPicker;
            searchTimer = setTimeout(function () {
                $.pjax.reload({
                    container: '#list-file-pjax',
                    url: baseUrl,
                    timeout: false
                });
            }, 500);
        });
    });
JS,
    \yii\web\View::POS_END
);
//
?>





