<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var yii\data\ActiveDataProvider $directoryDataProvider */
/* @var yii\data\ActiveDataProvider $fileDataProvider */
/* @var bool $isPicker */

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
echo Html::beginTag('span', [
    'class' => 'col-md-5 d-flex gap-2 mb-3']);

echo Html::tag(
    'span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'id' => 'searchFileInput',
        'placeholder' => Module::t('Search file..')
    ]) .
    Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), [
        'class' => 'input-group-text'
    ]),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload me-2', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload')),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'onclick' => 'openUploadModal()',
    ],
]);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-folder me-2', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('New Folder')),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-primary btn-md d-flex',
        'style' => 'min-width: 106px;',
        'onclick' => 'openNewFolderModal()',
    ],
]);
echo Html::endTag('span');

Pjax::begin([
    'id' => 'upload-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'new-folder-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'rename-folder-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'list-item-pjax',
    'timeout' => false,
    'enablePushState' => true,
    'enableReplaceState' => false,
    'clientOptions' => ['push' => true, 'replace' => false, 'history' => true],
]);

echo $this->render('_item-list', [
    'directoryDataProvider' => $directoryDataProvider,
    'fileDataProvider' => $fileDataProvider,
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

?>

<?php
$this->registerJs(
    <<<JS
    let currentDirectoryId = null;
    window.openFolder = function(id_directory, event) {
    if (event.target.classList.contains('folder-ellipsis') || 
        $(event.target).closest('.folder-dropdown-menu').length) {
        return;
    }
    currentDirectoryId = (id_directory === null || id_directory === undefined) ? null : id_directory; 

    let url = '/storage/default/index';
    if (id_directory) {
        url += '?id_directory=' + id_directory;
    }   
    $.pjax.reload({
        container: '#list-item-pjax',
        url: url,
        push: true, 
        replace: false,
        timeout: 10000,
        complete: function (){
            if (!url.includes('id_directory=')) 
                currentDirectoryId = null;
        }
    });
};
    
function openUploadModal() {
    event.preventDefault();
    let url = '/storage/default/upload-file';
    if (currentDirectoryId) {
        url += '?id_directory=' + currentDirectoryId;
    }
    else {
        url += '?id_directory=null';
    }
    $.pjax.reload({
        container: '#upload-file-pjax',
        type: 'GET',
        url: url,
    }).done(function() {
        setTimeout(function() {
            $('#uploadModal').modal('show');
        }, 1000);
    }).fail(function(e) {
        console.log('Error Modal:', e);
    });
}

$(document).on('click', '#uploadButton', function(e) {
    e.preventDefault();

    var form = document.getElementById('uploadForm');
    var formData = new FormData(form);
    
    if (currentDirectoryId !== null) 
        formData.append('id_directory', currentDirectoryId);
    else
        formData.append('id_directory', '');
    
    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function() {
            $('#uploadModal').modal('hide');
            if (currentDirectoryId) {
                $.pjax.reload({
                    container: "#list-item-pjax",
                    url: '/storage/default/index?id_directory=' + currentDirectoryId,
                    replace: false,
                    push: false,
                });
            } else {
                $.pjax.reload({
                container: "#list-item-pjax",
                url: '/storage/default/index',
                replace: false,
                push: false
                });
            }
        }
    });
});

    function openNewFolderModal() {
        event.preventDefault();
        let url = '/storage/default/new-folder';
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        $.pjax.reload({
            container: '#new-folder-pjax',
            type: 'GET',
            url: url,
        }).done(function() {
            setTimeout(function () {
                if ($('#newFolderModal').length) {
                    $('#newFolderModal').modal('show');
                } else {
                    $.pjax.reload({container: "#list-item-pjax"});
                }
            }, 1000);
        }).fail(function(e) {
            console.log('Error Modal:', e);
        });
    }
    $(document).on('click', '#createFolderButton', function(e) {
    e.preventDefault();

    var form = document.getElementById('newFolderForm');
    var formData = new FormData(form);
    
    if (currentDirectoryId) {
        formData.append('id_directory', currentDirectoryId); 
    } else {
        formData.append('id_directory', null); 
    }

    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        complete: function() {
            $('#newFolderModal').modal('hide');
            if (currentDirectoryId) {
                $.pjax.reload({
                    container: "#list-item-pjax",
                    url: '/storage/default/index?id_directory=' + currentDirectoryId,
                    replace: false,
                    push: false,
                });
            } else {
                $.pjax.reload({
                container: "#list-item-pjax",
                url: '/storage/default/index',
                replace: false,
                push: false
                });
            }
        }
    });
});


    function openRenameFolderModal(id) {
        event.preventDefault();
        $.pjax.reload({
            container: '#rename-folder-pjax',
            type: 'GET',
            url: '/storage/default/rename-folder',
            data: { id: id },
        }).done(function() {
            setTimeout(function () {
                if ($('#renameFolderModal').length) {
                    $('#renameFolderModal').modal('show');
                } else {
                    $.pjax.reload({container: "#list-item-pjax"});
                }
            }, 1000);
        }).fail(function(e) {
            console.log('Error Modal:', e);
        });
    }

    $(document).on('click', '#renameFolderButton', function(e) {
        e.preventDefault();

        var form = $('#renameFolderForm');
        
        $.ajax({
            url: form.attr('action') + "?id="+$("#renameFolderButton").data("id"),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                $('#renameFolderModal').modal('hide');
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

function deleteFolder(id) {
    $.ajax({
        url: '/storage/default/delete-folder',
        type: 'POST',
        data: { id: id },
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        },
        complete: function() {
            $.pjax.reload({container: "#list-item-pjax"});
        }
    });
}

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
                $.pjax.reload({container: "#list-item-pjax"});
            }
        },
        error: function() {
           $.pjax.reload({container: "#list-item-pjax"});
        }
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
                    $.pjax.reload({container: "#list-item-pjax"});
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
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

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
                $.pjax.reload({container: "#list-item-pjax"});
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
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

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
                $.pjax.reload({container: "#list-item-pjax"});
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
                $.pjax.reload({container: "#list-item-pjax"});
            },
            error: function() {
                $.pjax.reload({container: "#list-item-pjax"});
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
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    }
JS,
    \yii\web\View::POS_END
);
?>