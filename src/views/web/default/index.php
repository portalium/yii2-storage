<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;

StorageAsset::register($this);
$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

$fileExtensions = Yii::$app->request->get('fileExtensions') ?? ($fileExtensions ?? []);
$isPicker = $isPicker ?? false;

$this->registerJs("window.isPicker = " . ($isPicker ? 'true' : 'false') . ";", \yii\web\View::POS_HEAD);
$this->registerJs("window.fileExtensions = " . json_encode($fileExtensions) . ";", \yii\web\View::POS_HEAD);
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
echo Html::tag('br');
?>

<?php Pjax::begin(['id' => 'upload-file-pjax', 'enablePushState' => false, 'timeout' => false]); Pjax::end(); ?>

<?php Pjax::begin(['id' => 'list-file-pjax']); ?>
<?= $this->render('_file-list', [
    'dataProvider' => $dataProvider,
    'isPicker' => $isPicker
]) ?>
<?php Pjax::end(); ?>

<?php Pjax::begin(['id' => 'rename-file-pjax', 'enablePushState' => false]); Pjax::end(); ?>
<?php Pjax::begin(['id' => 'update-file-pjax', 'enablePushState' => false]); Pjax::end(); ?>
<?php Pjax::begin(['id' => 'share-file-pjax']); Pjax::end(); ?>

<?php
$this->registerJs(<<<JS
function getFileExtensionsFromUrl() {
    let urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('fileExtensions');
}

function refreshFileListDirect() {
    const fileExtensions = getFileExtensionsFromUrl();
    const pickerParam = window.isPicker ? '&isPicker=1' : '&isPicker=0';
    const url = '/storage/default/file-list' +
        (fileExtensions ? '?fileExtensions=' + encodeURIComponent(fileExtensions) + pickerParam : pickerParam ? '?' + pickerParam.substring(1) : '');

    $.ajax({
        url: url,
        type: 'GET',
        success: function(html) {
            $('#list-file-pjax').html(html);
        },
        error: function(e) {
            console.error("Liste g\u00fcncellenirken hata olu\u015ftu:", e);
        }
    });
}

function openUpdateModal(id) {
    event.preventDefault();

    $.pjax.reload({
        container: '#update-file-pjax',
        type: 'GET',
        url: '/storage/default/update-file',
        data: { id: id },
    }).done(function() {
        setTimeout(function () {
            if ($('#updateModal').length > 0) {
                $('#updateModal').modal('show');
            } else {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        }, 1000); 
    }).fail(function(e) {
        console.log('Error Modal:', e);
    });
    }

    $(document).on('click', '#updateButton', function(e) {
        e.preventDefault();

        var form = $('#updateForm')[0];
        var formData = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                $('#updateModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    });

    function copyFile(id) {
        event.preventDefault();
        
        $.ajax({
            url: '/storage/default/copy-file',
            type: 'POST',
            data: { id: id },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {   
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            },
            error: function() {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
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
            complete: function() {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    }

    function openShareModal(id) {
        event.preventDefault();
        $.pjax.reload({
            container: '#share-file-pjax',
            type: 'GET',
            url: '/storage/default/share-file',
            data: { id: id },
        }).done(function() {
            setTimeout(function() {
                $('#shareModal').modal('show');
            }, 1000);
        }).fail(function(e) {
            console.log('Error Modal', e);
        });
    }

    $(document).on('click', '#shareButton', function(e) {
        e.preventDefault();
        var form = $('#shareForm');
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                $('#shareModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    });

function openUploadModal() {
        event.preventDefault();
        $.pjax.reload({
            container: '#upload-file-pjax',
            type: 'GET',
            url: '/storage/default/upload-file',
        }).done(function() {
            setTimeout(function() {
                $('#uploadModal').modal('show');
            }, 1000);
        }).fail(function(e) {
            console.log('Error Modal:', e);
        });
    }

  


function openRenameModal(id) {
        event.preventDefault();

        $.pjax.reload({
            container: '#rename-file-pjax',
            type: 'GET',
            url: '/storage/default/rename-file',
            data: { id: id },
        }).done(function() {
            setTimeout(function () {
                if ($('#renameModal').length) {
                    $('#renameModal').modal('show');
                } else {
                    $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                        $.pjax.reload({container: '#list-file-pjax'});
                    });
                }
            }, 1000);
        }).fail(function(e) {
            console.log('Error Modal:', e);
        });
    }

    $(document).on('click', '#renameButton', function(e) {
        e.preventDefault();

        var form = $('#renameForm');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                $('#renameModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    });


function downloadFile(id) {
    event.preventDefault();

    $.post({
        url: '/storage/default/download-file',
        data: { id: id },
        xhrFields: { responseType: 'blob' },
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data, status, xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                const filename = disposition.split('filename=')[1]?.replace(/["']/g, '') || 'downloaded_file';
                const blobUrl = URL.createObjectURL(data);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = decodeURIComponent(filename);
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
            } else {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        },
        error: function() {
           $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
               $.pjax.reload({container: '#list-file-pjax'});
           });
        }
    });
}




$(document).on('click', '#uploadButton', function(e) {
    e.preventDefault();
    var form = document.getElementById('uploadForm');
    var formData = new FormData(form);

    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#uploadModal').modal('hide');
            refreshFileListDirect();
            $.pjax.reload({container: "#pjax-flash-message"});
        },
        error: function(error) {
            console.error("Upload error:", error);
            $('#uploadModal').modal('hide');
            $.pjax.reload({container: "#pjax-flash-message"});
        }
    });
});



$(document).ready(function () {
    $('#searchFileInput').on('keyup', function () {
        var q = $(this).val().trim();
        var isPicker = window.isPicker || false;
        var fileExtensions = window.fileExtensions || [];

        let url = '/storage/default/search?q=' + encodeURIComponent(q);

        if (isPicker && fileExtensions.length > 0) {
            url += '&fileExtensions=' + encodeURIComponent(fileExtensions.join(','));
        }

        url += '&isPicker=' + (isPicker ? '1' : '0');

        $.pjax.reload({
            container: '#list-file-pjax',
            url: url,
            timeout: false
        });
    });
});

//deneme

JS, \yii\web\View::POS_END);
?>
