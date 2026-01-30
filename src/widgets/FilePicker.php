<?php

namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\InputWidget;
use portalium\data\ActiveDataProvider;


class FilePicker extends InputWidget
{
    public $dataProvider;
    public $multiple = 0;
    public $isJson = 1;
    public $callbackName = null;
    public $manage = false;
    public $fileExtensions = null;
    public $allowedExtensions = null;
    public $attributes = ['id_storage'];
    public $isPicker = true;

    public function init()
    {
        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');

        $this->multiple = $this->options['multiple'] ?? $this->multiple;
        $this->isJson = $this->options['isJson'] ?? $this->isJson;
        $this->callbackName = $this->options['callbackName'] ?? $this->callbackName;
        $this->fileExtensions = $this->options['fileExtensions'] ?? $this->fileExtensions;
        $this->allowedExtensions = $this->options['allowedExtensions'] ?? $this->allowedExtensions;
        $this->isPicker = $this->options['isPicker'] ?? $this->isPicker;

        if (isset($this->options['attributes'])) {
            $this->attributes = $this->options['attributes'];
        } elseif (isset($_GET['attributes'])) {
            $this->attributes = is_string($_GET['attributes']) ?
                explode(',', $_GET['attributes']) : $_GET['attributes'];
        } elseif (isset($_POST['attributes'])) {
            $this->attributes = is_string($_POST['attributes']) ?
                explode(',', $_POST['attributes']) : $_POST['attributes'];
        }

        if (!is_array($this->attributes)) {
            $this->attributes = [$this->attributes];
        }

        $this->attributes = array_filter($this->attributes, function ($attr) {
            return !empty(trim($attr));
        });

        if (empty($this->attributes)) {
            $this->attributes = ['id_storage'];
        }
    }

    public function run()
    {
        $query = Storage::find();

        if (is_array($this->fileExtensions) && !empty($this->fileExtensions)) {
            $orConditions = ['or'];
            foreach ($this->fileExtensions as $extension) {
                $orConditions[] = ['like', 'name', $extension];
            }
            $query->andWhere($orConditions);
        }

        $this->dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);

        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        $realAttribute = $this->attribute;

        if (preg_match('/\](\w+)$/', $this->attribute ?? '', $matches)) {
            $realAttribute = $matches[1];
        } elseif (preg_match('/^(\w+)/', $this->attribute ?? '', $matches)) {
            $realAttribute = $matches[1];
        }

        $value = $this->model->{$realAttribute} ?? '';
        $decoded = json_decode($value, true);
        $idStorage = '';

        if ($this->multiple && is_array($decoded)) {
            $first = reset($decoded);
            $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
        } elseif (!empty($decoded)) {
            $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
        }
        
        $previewValue = '';
        if (!empty($idStorage)) {
            $previewValue = json_encode(['id_storage' => $idStorage]);
        }
        echo Html::hiddenInput('preview-file-' . $this->options['id'], $this->model->{$this->attribute} ?? '', ['id' => 'preview-file-' . $this->options['id']]);

        echo Html::script("window.fileExtensions = " . json_encode($this->fileExtensions ?? []) . ";");
        echo Html::script("window.isPicker = " . ($this->isPicker ? 'true' : 'false') . ";");

        echo Html::button('<span class="btn-text">' . Module::t('Select File') . '</span>', [
            'class' => 'btn btn-primary',
            'data-allowed-extensions' => json_encode($this->allowedExtensions ?? []),
            'onclick' => 'handleFilePickerClick(this, "' . $this->options['id'] . '", "' . $idStorage . '", ' . ($this->multiple ? 'true' : 'false') . ', ' . ($this->isJson ? 'true' : 'false') . ', "' . ($this->callbackName ?? '') . '", ' . ($this->isPicker ? 'true' : 'false') . ', ' . json_encode($this->attributes) . ', ' . json_encode($this->allowedExtensions ?? []) . ')'
        ]);

        echo Html::button('<span class="btn-text">' . Module::t('Preview File') . '</span>', [
            'class' => 'btn btn-primary ms-2',
            'onclick' => 'previewSelectedFile(this)',
            'style' => 'margin-right: 5px;'
        ]);

        $modalHtml = $this->render('@portalium/storage/views/web/default/_filePreviewModal');

        if (empty($this->view->params['storageFilePreviewModalRegistered'])) {
            $this->view->params['storageFilePreviewModalRegistered'] = true;

            $js = '(function(){'
                . 'if (!document.getElementById("file-preview-modal") && !window._storageFilePreviewModalRegistered) {'
                . 'window._storageFilePreviewModalRegistered = true;'
                . 'document.body.insertAdjacentHTML("beforeend", ' . json_encode($modalHtml) . ');'
                . '}'
                . '})();';

            $this->view->registerJs($js, \yii\web\View::POS_END);
        }

        $this->registerJsScript();
    }

    protected function registerJsScript()
{
    $js = <<<'JS'
// Modal registry - for modal level assignation
if (!window.modalRegistry) {
    window.modalRegistry = new Map();
}

function previewSelectedFile(button) {
    const $btn = $(button);
    const $container = $btn.closest('div');
    const $input = $container.find('input[type="hidden"][name*="preview-file"]');

    if ($input.length === 0) {
        console.warn('Hidden input bulunamadı.');
        return;
    }

    const rawValue = $input.val();
    
    if (!rawValue || rawValue.trim() === '') {
        console.warn('Dosya seçili değil.');
        return;
    }

    let value;
    try {
        value = JSON.parse(rawValue);
    } catch (e) {
        console.error('JSON parse hatası:', e);
        return;
    }

    var id_storage = value.id_storage || value;
    
    if (!id_storage) {
        console.warn('id_storage değeri bulunamadı.');
        return;
    }

    $.ajax({
        url: '/storage/default/get-file-attributes',
        type: 'GET',
        data: { id: id_storage },
        dataType: 'json',
        success: function(data) {
            if (!data.url) {
                console.warn('data-url alınamadı.');
                return;
            }

            const attributesRaw = JSON.stringify(data.attributes || {});

            if (typeof window.openFilePreview === 'function') {
                window.openFilePreview(data.url, attributesRaw);
            } else {
                console.warn('openFilePreview fonksiyonu bulunamadı.');
            }
        },
        error: function(err) {
            console.error('Dosya attributes alınamadı:', err);
        }
    });
}

if (!window.handleFilePickerClick) {
    window.handleFilePickerClick = function(btn, id, id_storage, multiple, isJson, callbackName, isPicker, attributes, allowedExtensions) {
        var $btn = $(btn);

        if ($btn.hasClass("btn-loading")) return;

        $btn.addClass("btn-loading").css("pointer-events", "none");
        
        window.currentAllowedExtensions = allowedExtensions || [];

        window.openFilePickerModal(id, id_storage, multiple, isJson, callbackName, isPicker, attributes, allowedExtensions);

        $(document).one('shown.bs.modal', '#file-picker-modal', function () {
            $btn.removeClass("btn-loading").css("pointer-events", "auto");
        });
    };
}

// Modal supporter functions
if (!window.updateFileCard) {
    window.updateFileCard = function(id_storage) {
        $('.file-card.active').removeClass('active');
        $('.file-card input[type="checkbox"]').prop('checked', false);
        if (!id_storage) return;

        let el;
        if (Array.isArray(id_storage)) {
            id_storage.forEach(id => {
                el = $('#file-picker-modal .file-card[data-id=' + id + ']');
                el.addClass('active');
                el.find('input[type="checkbox"]').prop('checked', true);
            });
        } else {
            el = $('#file-picker-modal .file-card[data-id=' + id_storage + ']');
            el.addClass('active');
            el.find('input[type="checkbox"]').prop('checked', true);
        }
    };
}

// Modal closing function
if (!window.closeModalById) {
    window.closeModalById = function(modalId) {
        console.log('Closing modal:', modalId);
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            if (modalId !== 'file-picker-modal') {
                window.modalRegistry.delete(modalId);
                setTimeout(() => {
                    if (modalEl && modalEl.parentNode) {
                        modalEl.parentNode.removeChild(modalEl);
                    }
                    if (window.modalRegistry.size === 0) {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        setTimeout(() => {
                            window.restoreMainPageEvents && window.restoreMainPageEvents();
                        }, 100);
                    }
                }, 300);
            }
        }
    };
}

// Legacy cleanup function
if (!window.cleanupModal) {
    window.cleanupModal = function(modalId = null, onlySpecific = false) {
        if (modalId) {
            window.closeModalById(modalId);
        } else {
            window.modalRegistry.forEach((value, key) => {
                window.closeModalById(key);
            });
        }
    };
}

// Function that connects modal-specific close events
if (!window.bindModalCloseEvents) {
    window.bindModalCloseEvents = function(modalId, level = 0) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        
        console.log('Binding close events for modal:', modalId, 'level:', level);
        
        window.modalRegistry.set(modalId, { level: level });
        
        const directCloseButtons = modalEl.querySelectorAll(
            ':scope > .modal-dialog > .modal-content > .modal-header .btn-close, ' +
            ':scope > .modal-dialog > .modal-content > .modal-footer .btn-close, ' +
            ':scope > .modal-dialog > .modal-content > .modal-footer [data-bs-dismiss="modal"]'
        );
        
        directCloseButtons.forEach(button => {
            $(button).off('click.modal-close-' + modalId);
            $(button).on('click.modal-close-' + modalId, function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                console.log('Direct close button clicked for:', modalId);
                window.closeModalById(modalId);
            });
        });
        
        modalEl.addEventListener('hidden.bs.modal', function(e) {
            if (modalId === 'file-picker-modal') {
                console.log('Ignoring hidden.bs.modal for file-picker-modal');
                return;
            }
            
            console.log('Modal hidden event for:', modalId);
            window.modalRegistry.delete(modalId);
            
            setTimeout(() => {
                if (modalEl && modalEl.parentNode) {
                    modalEl.parentNode.removeChild(modalEl);
                }
            }, 100);
        });
    };
}

// Function to restore home page events
if (!window.restoreMainPageEvents) {
    window.restoreMainPageEvents = function() {
        if ((!window.isPicker || window.isPicker === false) && window.loadedRestoreMainPageEvents == false) {
            window.loadedRestoreMainPageEvents = true;
            
            $(document).off('click.main-dropdown').on('click.main-dropdown', '.dropdown-toggle:not(.modal .dropdown-toggle), .file-ellipsis:not(.modal .file-ellipsis), .folder-ellipsis:not(.modal .folder-ellipsis)', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = $(this).closest('.dropdown');
                const menu = dropdown.find('.dropdown-menu');
                
                $('.dropdown-menu').not(menu).removeClass('show');
                $('.dropdown').not(dropdown).removeClass('show');
                
                dropdown.toggleClass('show');
                menu.toggleClass('show');
            });

            $(document).off('click.main-action').on('click.main-action', '.dropdown-item:not(.modal .dropdown-item), .dropdown-menu a:not(.modal .dropdown-menu a)', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const onclick = $(this).attr('onclick');
                const href = $(this).attr('href');
                
                $('.dropdown-menu').removeClass('show');
                $('.dropdown').removeClass('show');
                
                if (onclick) {
                    try { 
                        eval(onclick); 
                    } catch(error) { 
                        console.error('Onclick error:', error); 
                    }
                } else if (href) {
                    window.location.href = href;
                }
            });

            $(document).off('click.main-outside').on('click.main-outside', function(e) {
                if (!$(e.target).closest('.dropdown').length && !$(e.target).closest('.modal').length) {
                    $('.dropdown-menu').removeClass('show');
                    $('.dropdown').removeClass('show');
                }
            });
            
            console.log('Ana sayfa event\'leri geri yüklendi');
        }
    };
}

// Special event binding for file picker
if (!window.bindFilePickerEvents) {
    window.bindFilePickerEvents = function() {
        $('#file-picker-modal .btn-select').off('click.picker-select').on('click.picker-select', function(e) {
            e.stopPropagation();
            window.saveSelect();
        });

        $(document).off('click.picker-dropdown').on('click.picker-dropdown', '#file-picker-modal .dropdown-toggle, #file-picker-modal .file-ellipsis, #file-picker-modal .folder-ellipsis', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = $(this).closest('.dropdown');
            const menu = dropdown.find('.dropdown-menu');
            
            $('#file-picker-modal .dropdown-menu').not(menu).removeClass('show');
            $('#file-picker-modal .dropdown').not(dropdown).removeClass('show');
            
            dropdown.toggleClass('show');
            menu.toggleClass('show');
        });

        $(document).off('click.picker-outside').on('click.picker-outside', '#file-picker-modal', function(e) {
            if (!$(e.target).closest('#file-picker-modal .dropdown').length) {
                $('#file-picker-modal .dropdown-menu').removeClass('show');
                $('#file-picker-modal .dropdown').removeClass('show');
            }
        });
    };
}

// Action handler
if (!window.handlePickerAction) {
    window.handlePickerAction = function(action, id, href) {
        switch(action) {
            case 'download':
                $.post('/storage/default/download-file', { id: id, isPicker: '1' })
                    .done(() => window.refreshFilePicker && window.refreshFilePicker());
                break;
            case 'copy':
                $.post('/storage/default/copy-file', { id: id, isPicker: '1' })
                    .done(() => window.refreshFilePicker && window.refreshFilePicker());
                break;
            case 'delete':
                if (confirm('Silmek istediğinizden emin misiniz?')) {
                    $.post('/storage/default/delete-file', { id: id, isPicker: '1' })
                        .done(() => window.refreshFilePicker && window.refreshFilePicker());
                }
                break;
            case 'rename':
            case 'update':
            case 'share':
                if (href) window.openActionModal(action, href);
                break;
            case 'delete-folder':
                if (confirm('Klasörü silmek istediğinizden emin misiniz?')) {
                    $.post('/storage/default/delete-folder', { id: id, isPicker: '1' })
                        .done(() => window.refreshFilePicker && window.refreshFilePicker());
                }
                break;
        }
    }
};

// Action open modal
if (!window.openActionModal) {
    window.openActionModal = function(action, href) {
        const modalId = 'action-modal-' + Date.now();
        
        // Show loading indicator for share modal
        if (action === 'share') {
            window.showLoading && window.showLoading('Paylaşım ekranı açılıyor...');
            
            // Wait 1 second before sending request
            setTimeout(function() {
                sendActionModalRequest(action, href, modalId);
            }, 1000);
        } else {
            sendActionModalRequest(action, href, modalId);
        }
    };
    
    function sendActionModalRequest(action, href, modalId) {
        $.get(href)
            .done(function(response) {
                $('.modal[id*="action-modal"], .modal[id*="Modal"]:not(#file-picker-modal), .modal[id*="modal-"]:not(#file-picker-modal)').each(function() {
                    window.closeModalById(this.id);
                });
                
                const idMap = {
                    'rename': 'renameModal',
                    'update': 'updateModal', 
                    'share': 'modal-share'
                };
                const oldId = idMap[action] || 'modal';
                response = response.replace(new RegExp('id="' + oldId + '"', 'g'), 'id="' + modalId + '"');
                
                $('body').append(response);
                
                setTimeout(() => {
                    // Hide loading indicator
                    if (action === 'share') {
                        window.hideLoading && window.hideLoading();
                    }
                    
                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        window.bindModalCloseEvents(modalId, 1);
                        
                        const modal = new bootstrap.Modal(modalEl, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        modal.show();
                        
                        $(modalEl).find('form').on('submit', function(e) {
                            e.preventDefault();
                            $.ajax({
                                url: this.action,
                                type: 'POST',
                                data: new FormData(this),
                                processData: false,
                                contentType: false,
                                complete: function() {
                                    window.closeModalById(modalId);
                                    window.refreshFilePicker && window.refreshFilePicker();
                                }
                            });
                        });
                    }
                }, 100);
            })
            .fail(function(e) {
                console.log('Error loading modal:', e);
                // Hide loading indicator on error
                if (action === 'share') {
                    window.hideLoading && window.hideLoading();
                }
            });
    }
}

// Main file picker modal opening
if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName, isPicker = true, attributes = ['id_storage'], allowedExtensions = []) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;
        window.isPicker = isPicker;
        window.currentAttributes = Array.isArray(attributes) ? attributes : [attributes];
        window.allowedExtensions = allowedExtensions || [];

        let inputValue = $('#' + id).val();
        let parsedValue = {};

        try {
            parsedValue = JSON.parse(inputValue || '{}');
        } catch (e) {
            parsedValue = {};
        }

        let id_storage_2 = parsedValue.id_storage ?? null;

        window.selectedIdStorage =
            (id_storage_2 !== null && !isNaN(id_storage_2))
                ? id_storage_2
                : inputValue;

        if (document.getElementById('file-picker-modal')) {
            window.closeModalById('file-picker-modal');
        }

        const savedSortField = localStorage.getItem('sortField');
        const savedSortDirection = localStorage.getItem('sortDirection');
        
        const modalParams = {
            id: id,
            multiple: multiple,
            isJson: isJson,
            fileExtensions: window.fileExtensions,
            isPicker: isPicker,
            attributes: window.currentAttributes,
            selectedFileId: id_storage_2 || inputValue || null,
            allowedExtensions: allowedExtensions
        };
        
        if (savedSortField) {
            modalParams.sortField = savedSortField;
            modalParams.sortDirection = savedSortDirection || 'desc';
        }
        
        $.get('/storage/default/picker-modal', modalParams).done(function(response) {
            $('#file-picker-modal').remove();
            $('body').append(response);

            const modalEl = document.getElementById('file-picker-modal');
            if (modalEl) {
                window.pjaxBaseUrl = '/storage/default/picker-modal?isPicker=1';
                if (id_storage_2 || inputValue) {
                    window.pjaxBaseUrl += '&selectedFileId=' + (id_storage_2 || inputValue);
                }
                if (window.fileExtensions && window.fileExtensions.length > 0) {
                    window.pjaxBaseUrl += '&fileExtensions=' + window.fileExtensions.join(',');
                }
                if (savedSortField) {
                    window.pjaxBaseUrl += '&sortField=' + savedSortField;
                    window.pjaxBaseUrl += '&sortDirection=' + (savedSortDirection || 'desc');
                }
                window.bindModalCloseEvents('file-picker-modal', 0);
                
                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });

                const savedMode = localStorage.getItem('viewMode') || 'grid';
                if (typeof applyViewModeClasses === 'function') {
                    applyViewModeClasses(savedMode);
                }
                
                modal.show();
                
                modalEl.addEventListener('shown.bs.modal', function() {
                    if (typeof updateSortDirectionLabels === 'function') {
                        updateSortDirectionLabels();
                    }
                    if (typeof highlightActiveSort === 'function') {
                        highlightActiveSort();
                    }
                    
                    if(id_storage_2 && !isNaN(id_storage_2)) {
                        window.updateFileCard(id_storage_2);
                    }else{
                        window.updateFileCard(inputValue);
                    }
                    window.bindFilePickerEvents();
                }, { once: true });
            }
        });
    };
}

$(document).ready(function() {
    window.restoreMainPageEvents();
});

// Supporting functions
if (!window.refreshFilePicker) {
    window.refreshFilePicker = function() {
        const container = $('#file-picker-modal .files-container');
        if (container.length) {
            $.get('/storage/default/picker-content', {
                fileExtensions: window.fileExtensions,
                isPicker: window.isPicker,
                attributes: window.currentAttributes
            }).done(function(response) {
                container.html(response);
                window.bindFilePickerEvents();
                
                if (typeof updateSortDirectionLabels === 'function') {
                    updateSortDirectionLabels();
                }
                if (typeof highlightActiveSort === 'function') {
                    highlightActiveSort();
                }
                
                const id_storage = window.currentSelectedIdStorage || null;
                if (window.updateFileCard) {
                    window.updateFileCard(id_storage);
                }
            });
        }
    };
}

if (!window.getAttributesFromDOM) {
    window.getAttributesFromDOM = function(id) {
        let el = document.querySelector('[data-id="' + id + '"]');
        if (el) {
            let fileItem = el.querySelector('.file-item');
            if (fileItem) {
                el = fileItem;
            }
        }

        if (!el) return {};
        try {
            const attr = el.getAttribute('data-attributes') || el.getAttribute('attributes');
            return attr ? JSON.parse(attr) : {};
        } catch (e) {
            console.error('Error parsing attributes:', e);
            return {};
        }
    };
}

if (!window.saveSelect) {
    window.saveSelect = function() {
        const attributes = window.currentAttributes || ['id_storage'];
        
        const selectedFiles = window.multiple ?
            $('.file-card input[type="checkbox"]:checked').map(function() {
                return $(this).closest('.file-card').data('id');
            }).get() :
            $('.file-card.active').data('id');
            
        let value;
        if (window.isJson) {
            if (window.multiple) {
                value = JSON.stringify(selectedFiles.map(id => {
                    const fullData = getAttributesFromDOM(id);
                    const obj = {};
                    attributes.forEach(attr => {
                        obj[attr] = fullData[attr] || null;
                    });
                    return obj;
                }));
            } else {
                const fullData = getAttributesFromDOM(selectedFiles);
                if (attributes.length === 1) {
                    value = JSON.stringify(fullData[attributes[0]] || null);
                } else {
                    const obj = {};
                    attributes.forEach(attr => {
                        obj[attr] = fullData[attr] || null;
                    });
                    value = JSON.stringify(obj);
                }
            }
        } else {
            value = window.multiple ? selectedFiles.join(',') : selectedFiles;
        }

        $('#' + window.inputId).val(value);
        $('#preview-file-' + window.inputId).val(value);
        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            window[window.callbackName](selectedFiles);
        }

        window.closeModalById('file-picker-modal');
    };
}
JS;

    $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
}
}
