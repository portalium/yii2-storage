<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;

/** @var $this yii\web\View */
/** @var $form portalium\theme\widgets\ActiveForm */
/** @var yii\data\ActiveDataProvider $directoryDataProvider */
/** @var yii\data\ActiveDataProvider $fileDataProvider */
/** @var bool $isPicker */

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
echo Html::beginTag('span', [
    'class' => 'col-md-5 d-flex gap-2 mb-3'
]);

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
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);
Pjax::end();

?>

<?php
$this->registerJs(
    <<<JS
    
    window.isPicker = false;
    
    let currentDirectoryId = null;
    let currentIsPicker = $('#searchFileInput').data('is-picker') === 1;
    let searchTimer;
    let isSearching = false;
    let originalUrl = window.location.href;
    

    function isInWidgetContext() {
        return window.location.href.includes('picker-modal') || 
               window.top !== window.self || 
               window.frameElement !== null;
    }
    
  
    function showModal(modalId, timeout = 200) {
        setTimeout(function() {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                
                const existingModal = bootstrap.Modal.getInstance(modalEl);
                if (existingModal) {
                    existingModal.dispose();
                }
                
                
                const modalInstance = new bootstrap.Modal(modalEl, {
                    backdrop: true,
                    keyboard: true
                });
                modalInstance.show();
            } else {
                console.warn('Modal element not found:', modalId);
            }
        }, timeout);
    }
    
  
    function hideModal(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
            
           
            setTimeout(() => {
                if (modalEl && modalEl.parentNode && modalEl.id !== 'file-picker-modal') {
                    modalEl.parentNode.removeChild(modalEl);
                }
            }, 300);
        }
    }
    
    function getBaseUrl() {
        let url = '/storage/default/index';
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        if (currentIsPicker) {
            const separator = url.includes('?') ? '&' : '?';
            url += separator + 'isPicker=1';
        }
        return url;
    }
    
    function returnToMainPage() {
        isSearching = false;
        const baseUrl = getBaseUrl();
        
        $.pjax.reload({
            container: '#list-item-pjax',
            url: baseUrl,
            push: false,
            replace: false,
            timeout: 10000,
            complete: function() {
                console.log('Ana sayfaya döndü, pagination restore edildi');
            }
        });
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
        
        if (currentIsPicker) {
            const separator = url.includes('?') ? '&' : '?';
            url += separator + 'isPicker=1';
        }
        
        isSearching = false;
        $('#searchFileInput').val('');
        
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
        let url = '/storage/default/upload-file';
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        else {
            url += '?id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.pjax.reload({
            container: '#upload-file-pjax',
            type: 'GET',
            url: url,
        }).done(function() {
            setTimeout(function() {
                showModal('uploadModal');
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
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }
        
        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                hideModal('uploadModal');
                
                if (isSearching) {
                    const searchValue = $('#searchFileInput').val().trim();
                    if (searchValue) {
                        performSearch(searchValue);
                        return;
                    }
                }
                
                const reloadUrl = getBaseUrl();
                $.pjax.reload({
                    container: "#list-item-pjax",
                    url: reloadUrl,
                    replace: false,
                    push: false
                });
            }
        });
    });

   
    function openNewFolderModal(event) {
        event.preventDefault();
        let url = '/storage/default/new-folder';
        
        if (currentDirectoryId) {
            url += '?id_directory=' + currentDirectoryId;
        }
        else {
            url += '?id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                
                $('.modal[id^="newFolderModal"]').remove();
                
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
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }

        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            complete: function() {
                hideModal('newFolderModal');
                
                if (isSearching) {
                    const searchValue = $('#searchFileInput').val().trim();
                    if (searchValue) {
                        performSearch(searchValue);
                        return;
                    }
                }
                
                const reloadUrl = getBaseUrl();
                $.pjax.reload({
                    container: "#list-item-pjax",
                    url: reloadUrl,
                    replace: false,
                    push: false,
                });
            }
        });
    });

   
    function openRenameFolderModal(id) {
        event.preventDefault();
        let url = '/storage/default/rename-folder?id=' + id;
        if (currentDirectoryId) {
            url += '&id_directory=' + currentDirectoryId;
        }
        else {
            url += '&id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('.modal[id^="renameFolderModal"]').remove();
                
                $('#rename-folder-pjax').html(response);
                setTimeout(function () {
                    if ($('#renameFolderModal').length) {
                        showModal('renameFolderModal');
                    } else {
                        refreshCurrentView();
                    }
                }, 100);
            },
            error: function(e) {
                console.log('Error Modal:', e);
                refreshCurrentView();
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
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }
        
        $.ajax({
            url: '/storage/default/rename-folder?id=' + $('#renameFolderButton').data('id') + '&id_directory=' + currentDirectoryId,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            complete: function() {
                hideModal('renameFolderModal');
                refreshCurrentView();
            }
        });
    });

    function deleteFolder(id) {
       event.preventDefault();
        
        $.ajax({
            url: '/storage/default/delete-folder?id_directory=' + (currentDirectoryId || 'null') + '&id=' + id,
            type: 'POST',
            data: currentIsPicker ? { isPicker: '1' } : {},
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            complete: function() {
                refreshCurrentView();
            }
        });
    }
    
    function downloadFile(id) {
        $.post({
            url: '/storage/default/download-file',
            data: { 
                id: id,
                isPicker: currentIsPicker ? '1' : '0'
            },
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
                }
                
                refreshCurrentView();
            },
            error: function() {
                refreshCurrentView();
            }
        });
    }

   
    function openRenameModal(id) {
        event.preventDefault();
        let url = '/storage/default/rename-file?id=' + id;
        if (currentDirectoryId) {
            url += '&id_directory=' + currentDirectoryId;
        }
        else {
            url += '&id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('.modal[id^="renameModal"]').remove();
                
                $('#rename-file-pjax').html(response);
                setTimeout(function () {
                    if ($('#renameModal').length) {
                        showModal('renameModal');
                    } else {
                        refreshCurrentView();
                    }
                }, 100);
            },
            error: function(e) {
                console.log('Error Modal:', e);
                refreshCurrentView();
            }
        });
    }

    $(document).on('click', '#renameButton', function(e) {
        e.preventDefault();
        var form = document.getElementById('renameForm');
        var formData = new FormData(form);
        
        if (currentDirectoryId) {
            formData.append('id_directory', currentDirectoryId); 
        } else {
            formData.append('id_directory', 'null'); 
        }
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }
        
         $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            complete: function() {
                hideModal('renameModal');
                refreshCurrentView();
            }
        });
    });
        
 
    function openUpdateModal(id ) {
        event.preventDefault();
        let url = '/storage/default/update-file?id=' + id;
        if (currentDirectoryId) {
            url += '&id_directory=' + currentDirectoryId;
        }
        else {
            url += '&id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('.modal[id^="updateModal"]').remove();
                
                $('#update-file-pjax').html(response);
                setTimeout(function () {
                    if ($('#updateModal').length) {
                        showModal('updateModal');
                    } else {
                        refreshCurrentView();
                    }
                }, 100);
            },
            error: function(e) {
                console.log('Error Modal:', e);
                refreshCurrentView();
            }
        });
    }

    $(document).on('click', '#updateButton', function(e) {
        e.preventDefault();
        
        var form = document.getElementById('updateForm');
        var formData = new FormData(form);
        
        if (currentDirectoryId) {
            formData.append('id_directory', currentDirectoryId); 
        } else {
            formData.append('id_directory', 'null'); 
        }
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }
        
        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            complete: function() {
                hideModal('updateModal');
                refreshCurrentView();
            }
        });
    });

   
    function openShareModal(id) {
        event.preventDefault();
        let url = '/storage/default/share-file?id=' + id;
        if (currentDirectoryId) {
            url += '&id_directory=' + currentDirectoryId;
        } else {
            url += '&id_directory=null';
        }
        
        if (currentIsPicker) {
            url += '&isPicker=1';
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('.modal[id^="shareModal"]').remove();
                
                $('#share-file-pjax').html(response);
                setTimeout(function () {
                    if ($('#shareModal').length) {
                        showModal('shareModal');
                    } else {
                        refreshCurrentView();
                    }
                }, 100);
            },
            error: function(e) {
                console.log('Error Modal:', e);
                refreshCurrentView();
            }
        });
    }

    $(document).on('click', '#shareButton', function(e) {
        e.preventDefault();
        
        var form = document.getElementById('shareForm');
        var formData = new FormData(form);
        
        if (currentDirectoryId) {
            formData.append('id_directory', currentDirectoryId); 
        } else {
            formData.append('id_directory', 'null'); 
        }
        
        if (currentIsPicker) {
            formData.append('isPicker', '1');
        }
        
        $.ajax({
            url: form.action + '?id_directory=' + (currentDirectoryId || 'null') + '&id=' + $('#shareButton').data('id'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            complete: function() {
                hideModal('shareModal');
                refreshCurrentView();
            }
        });
    });

    function copyFile(id) {
        event.preventDefault();
        
        $.ajax({
            url: '/storage/default/copy-file',
            type: 'POST',
            data: { 
                id: id,
                id_directory: currentDirectoryId || null,
                isPicker: currentIsPicker ? '1' : '0'
            },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                refreshCurrentView();
            }
        });
    }

    function deleteFile(id) {
        event.preventDefault();
        
        $.ajax({
            url: '/storage/default/delete-file',
            type: 'POST',
            data: { 
                id: id,
                id_directory: currentDirectoryId || null,
                isPicker: currentIsPicker ? '1' : '0'
            },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            complete: function() {
                refreshCurrentView();
            }
        });
    }
        
    function refreshCurrentView() {
        if (isSearching) {
            const searchValue = $('#searchFileInput').val().trim();
            if (searchValue) {
                performSearch(searchValue);
            } else {
                returnToMainPage();
            }
        } else {
            const reloadUrl = getBaseUrl();
            $.pjax.reload({
                container: "#list-item-pjax",
                url: reloadUrl,
                replace: false,
                push: false,
            });
        }
    }
    
    function performSearch(query) {
        if (!query || query.trim() === '') {
            returnToMainPage();
            return;
        }
        
        isSearching = true;
        const isPicker = $('#searchFileInput').data('is-picker') ? 1 : 0;
        const fileExtensions = Array.isArray(window.fileExtensions) ? window.fileExtensions.join(',') : '';
        let finalUrl = '/storage/default/search?q=' + encodeURIComponent(query) + '&isPicker=' + isPicker;
        
        if (currentDirectoryId !== null) {
            finalUrl += '&id_directory=' + currentDirectoryId;
        }
        
        if (fileExtensions) {
            finalUrl += '&fileExtensions=' + encodeURIComponent(fileExtensions);
        }
        
        const container = isInWidgetContext() ? '#list-file-pjax' : '#list-item-pjax';
        
        $.pjax.reload({
            container: container,
            url: finalUrl,
            timeout: 10000,
            push: false,
            replace: false
        });
    }
    
    async function refreshFileList() {
        return await new Promise((resolve, reject) => {
            const container = isInWidgetContext() ? '#list-file-pjax' : '#list-item-pjax';
            
            if ($(container).length) {
                let refreshUrl = '/storage/default/file-list';
                if (currentIsPicker) {
                    refreshUrl += '?isPicker=1';
                }
                
                $.pjax.reload({
                    container: container,
                    timeout: false,
                    url: refreshUrl,
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
        $(document).off('keyup.search input.search').on('keyup.search input.search', '#searchFileInput', function () {
            clearTimeout(searchTimer);
            const q = $(this).val().trim();
            
            searchTimer = setTimeout(function () {
                if (q === '') {
                    console.log('Arama kutusu boş, ana sayfaya dönülüyor...');
                    returnToMainPage();
                } else {
                    console.log('Arama yapılıyor:', q);
                    performSearch(q);
                }
            }, 500);
        });
    }
    
   
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
        console.log('Search binding initialized');
    });

    $(document).on('pjax:end', function () {
        bindSearchInput();
        console.log('Search binding refreshed after pjax');
    });
JS,
    \yii\web\View::POS_END
);
 
?>