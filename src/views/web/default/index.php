<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;

/** @var $this yii\web\View */
/** @var $form portalium\theme\widgets\ActiveForm */
/* @var yii\data\ActiveDataProvider $directories */
/* @var yii\data\ActiveDataProvider $files */
/** @var \yii\data\Pagination $pagination */
/** @var bool $isPicker */

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
        'placeholder' => Module::t('Search file..'),
        'data-is-picker' => $isPicker ? '1' : '0',
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
        'onclick' => 'openUploadModal(event)',
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
        'onclick' => 'openNewFolderModal(event)',
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
    'directories' => $directories,
    'files' => $files,
    'pagination' => $pagination,
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
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);
Pjax::end();

?>

<?php
$this->registerJs(
    <<<JS
    let currentDirectoryId = null;
    

    function isInWidgetContext() {
        return window.location.href.includes('picker-modal') || 
               window.top !== window.self || 
               window.frameElement !== null;
    }
    
    
    function showModal(modalId, timeout = 200) {
        setTimeout(function() {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                if (typeof bootstrap !== 'undefined') {
                    const modalInstance = new bootstrap.Modal(modalEl);
                    modalInstance.show();
                } else {
                    $(modalEl).modal('show');
                }
            } else {
                console.warn('Modal element not found:', modalId);
            }
        }, timeout);
    }
    

    function hideModal(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            if (typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                else $(modalEl).modal('hide');
            } else {
                $(modalEl).modal('hide');
            }
        }
    }
    
    window.openFolder = function(id_directory, event) {
        if (event && (event.target.classList.contains('folder-ellipsis') || 
            $(event.target).closest('.folder-dropdown-menu').length)) {
            return;
        }
        currentDirectoryId = (id_directory === null || id_directory === undefined) ? null : parseInt(id_directory); 

        let url = '/storage/default/index';
        if (id_directory) {
            url += '?id_directory=' + id_directory;
        }
        
        
        if ($('#searchFileInput').data('is-picker') === 1) {
            const separator = url.includes('?') ? '&' : '?';
            url += separator + 'isPicker=1';
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
    
    function openUploadModal(event) {
        if (event) event.preventDefault();
        let url = '/storage/default/upload-file';
        
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        else {
            url += '?id_directory=null';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#upload-file-pjax').html(response);
                showModal('uploadModal');
            },
            error: function(e) {
                console.error('Error loading upload modal:', e);
            }
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
                hideModal('uploadModal');
                
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
            },
            error: function(xhr, status, error) {
                console.error('Error uploading file:', error);
            }
        });
    });

    function openNewFolderModal(event) {
        if (event) event.preventDefault();
        let url = '/storage/default/new-folder';
        
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        else {
            url += '?id_directory=null';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#new-folder-pjax').html(response);
                showModal('newFolderModal');
            },
            error: function(e) {
                console.error('Error loading new folder modal:', e);
            }
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
                hideModal('newFolderModal');
                
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

    function openRenameFolderModal(id, event) {
        if (event) event.preventDefault();
        let url = '/storage/default/rename-folder?id=' + id;
        
        if (currentDirectoryId) {
            url += '&id_directory=' + currentDirectoryId;
        }
        else {
            url += '&id_directory=null';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#rename-folder-pjax').html(response);
                showModal('renameFolderModal');
            },
            error: function(e) {
                console.error('Error loading rename folder modal:', e);
            }
        });
    }

    $(document).on('click', '#renameFolderButton', function(e) {
        e.preventDefault();

        var form = document.getElementById('renameFolderForm');
        var formData = new FormData(form);
        
        if (currentDirectoryId) {
            formData.append('id_directory', currentDirectoryId); 
        } else {
            formData.append('id_directory', 'null'); 
        }
        
        $.ajax({
            url: form.action + '?id_directory=' + currentDirectoryId + '&id=' + $('#renameFolderButton').data('id'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                hideModal('renameFolderModal');
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

    function deleteFolder(id, event) {
        if (event) event.preventDefault();
        
        $.ajax({
            url: '/storage/default/delete-folder?id_directory=' + (currentDirectoryId || 'null') + '&id=' + id,
            type: 'POST',
            data: {},
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            complete: function() {
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
    }

    function downloadFile(id, event) {
        if (event) event.preventDefault();
        
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

    function openRenameModal(id, event) {
        if (event) event.preventDefault();
        
        $.ajax({
            url: '/storage/default/rename-file',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#rename-file-pjax').html(response);
                showModal('renameModal');
            },
            error: function(e) {
                console.error('Error loading rename modal:', e);
            }
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
                hideModal('renameModal');
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

    function openUpdateModal(id, event) {
        if (event) event.preventDefault();
        
        $.ajax({
            url: '/storage/default/update-file',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#update-file-pjax').html(response);
                showModal('updateModal');
            },
            error: function(e) {
                console.error('Error loading update modal:', e);
            }
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
                hideModal('updateModal');
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

    function openShareModal(id, event) {
        if (event) event.preventDefault();
        
        $.ajax({
            url: '/storage/default/share-file',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#share-file-pjax').html(response);
                showModal('shareModal');
            },
            error: function(e) {
                console.error('Error loading share modal:', e);
            }
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
                hideModal('shareModal');
                $.pjax.reload({container: "#list-item-pjax"});
            }
        });
    });

    function copyFile(id, event) {
        if (event) event.preventDefault();
        
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

    function deleteFile(id, event) {
        if (event) event.preventDefault();
        
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
    
    async function refreshFileList() {
        return await new Promise((resolve, reject) => {
            const container = isInWidgetContext() ? '#list-file-pjax' : '#list-item-pjax';
            
            if ($(container).length) {
                $.pjax.reload({
                    container: container,
                    timeout: false,
                    url: '/storage/default/file-list',
                    complete: function() {
                        resolve();
                    }
                });
            } else {
                reject('File list container not found');
            }
        });
    }
    
    function bindSearchInput() {
        let searchTimer;
        $(document).off('keyup.search').on('keyup.search', '#searchFileInput', function () {
            clearTimeout(searchTimer);
            const q = $(this).val().trim();
            const isPicker = $(this).data('is-picker') ? 1 : 0;
            const fileExtensions = Array.isArray(window.fileExtensions) ? window.fileExtensions.join(',') : '';
            
            let finalUrl = '/storage/default/search?q=' + encodeURIComponent(q) + '&isPicker=' + isPicker;
            
            // Add current directory if available
            if (currentDirectoryId !== null) {
                finalUrl += '&id_directory=' + currentDirectoryId;
            }
            
            if (fileExtensions) {
                finalUrl += '&fileExtensions=' + encodeURIComponent(fileExtensions);
            }
            
            const container = isInWidgetContext() ? '#list-file-pjax' : '#list-item-pjax';
            
            searchTimer = setTimeout(function () {
                $.pjax.reload({
                    container: container,
                    url: finalUrl,
                    timeout: 10000
                });
            }, 500);
        });
    }
    
    // File actions event bindings
    $(document).off('click.fileActions').on('click.fileActions', '.file-action', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const action = $(this).data('action');
        const id = $(this).closest('[data-id]').data('id');
        
        if (!id) return;
        
        switch(action) {
            case 'copy':
                copyFile(id, e);
                break;
            case 'delete':
                deleteFile(id, e);
                break;
            case 'download':
                downloadFile(id, e);
                break;
            case 'rename':
                openRenameModal(id, e);
                break;
            case 'update':
                openUpdateModal(id, e);
                break;
            case 'share':
                openShareModal(id, e);
                break;
        }
    });
    
    // Folder actions event bindings
    $(document).off('click.folderActions').on('click.folderActions', '.folder-action', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const action = $(this).data('action');
        const id = $(this).closest('[data-id]').data('id');
        
        if (!id) return;
        
        switch(action) {
            case 'delete':
                deleteFolder(id, e);
                break;
            case 'rename':
                openRenameFolderModal(id, e);
                break;
        }
    });

    // Make functions available globally
    window.openRenameModal = openRenameModal;
    window.openUpdateModal = openUpdateModal;
    window.openShareModal = openShareModal;
    window.openRenameFolderModal = openRenameFolderModal;
    window.downloadFile = downloadFile;
    window.copyFile = copyFile;
    window.deleteFile = deleteFile;
    window.deleteFolder = deleteFolder;

    $(document).ready(function () {
        bindSearchInput();
    });

    $(document).on('pjax:end', function () {
        bindSearchInput();
    });
JS,
    \yii\web\View::POS_END
);
?>