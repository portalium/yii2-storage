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

        $value = $this->model->{$this->attribute} ?? '';
        $decoded = json_decode($value, true);
        $idStorage = '';

        if ($this->multiple && is_array($decoded)) {
            $first = reset($decoded);
            $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
        } elseif (!empty($decoded)) {
            $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
        }


        echo Html::script("window.fileExtensions = " . json_encode($this->fileExtensions ?? []) . ";");
        echo Html::script("window.isPicker = " . ($this->isPicker ? 'true' : 'false') . ";");

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'window.openFilePickerModal("' . $this->options['id'] . '", "' . $idStorage . '", ' . ($this->multiple ? 'true' : 'false') . ', ' . ($this->isJson ? 'true' : 'false') . ', "' . ($this->callbackName ?? '') . '", ' . ($this->isPicker ? 'true' : 'false') . ', ' . json_encode($this->attributes) . ')'
        ]);

        $this->registerJsScript();
    }

    protected function registerJsScript()
    {
        $js = <<<JS
const updateFileCard = function(id_storage) {
    $('.file-card.active').removeClass('active');
    $('.file-card input[type="checkbox"]').prop('checked', false);
    if (!id_storage) return;

    if (Array.isArray(id_storage)) {
        id_storage.forEach(id => {
            let el = $('#file-picker-modal span[data-id="' + id + '"]');
            el.addClass('active');
            el.find('input[type="checkbox"]').prop('checked', true);
        });
    } else {
        let el = $('#file-picker-modal span[data-id="' + id_storage + '"]');
        el.addClass('active');
        el.find('input[type="checkbox"]').prop('checked', true);
    }
};

const forceReflow = function() {
    const container = document.querySelector('#file-picker-modal .files-container');
    if (container) {
        container.classList.remove('d-none');
        void container.offsetWidth;
        container.classList.add('d-flex');
    }
};


const cleanupModal = function(modalId = null) {
    if (modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
            
            setTimeout(() => {
                if (modalEl && modalEl.parentNode) {
                    modalEl.parentNode.removeChild(modalEl);
                }
            }, 300);
        }
    } else {
        const modalEl = document.getElementById('file-picker-modal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    }
    
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
};


const bindModalButtons = function() {
    $(document).off('click.btn-select').on('click.btn-select', '#file-picker-modal .btn-select', function() {
        window.saveSelect();
    });

    $(document).off('click.btn-close').on('click.btn-close', '#file-picker-modal .btn-close, #file-picker-modal .close', function() {
        cleanupModal();
    });
};


const bindFileActions = function() {
    
    const context = window.isPicker ? '#file-picker-modal' : document;
    
    
    $(context).off('click.picker-share').on('click.picker-share', '.btn-share', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        let url = $(this).attr('href');
        let modalId = 'modal-share-' + Date.now();
        
        $.get(url, function(response) {
           
            $('.modal[id^="modal-share"]').remove();
            
            
            let modifiedResponse = response.replace(/id="modal-share"/g, 'id="' + modalId + '"');
            
            $('body').append(modifiedResponse);
            
            setTimeout(() => {
                const shareModalEl = document.getElementById(modalId);
                if (shareModalEl) {
                    const shareModal = new bootstrap.Modal(shareModalEl, {
                        backdrop: true,
                        keyboard: true
                    });
                    shareModal.show();
                    
                    
                    shareModalEl.addEventListener('hidden.bs.modal', function() {
                        setTimeout(() => {
                            if (shareModalEl && shareModalEl.parentNode) {
                                shareModalEl.parentNode.removeChild(shareModalEl);
                            }
                        }, 100);
                    });
                }
            }, 100);
        }).fail(function() {
            console.error('Share modal yüklenemedi');
        });
    });

    
    $(context).off('click.picker-rename').on('click.picker-rename', '.btn-rename', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        let url = $(this).attr('href');
        let modalId = 'modal-rename-' + Date.now();
        
        $.get(url, function(response) {
            $('.modal[id^="modal-rename"]').remove();
            
            let modifiedResponse = response.replace(/id="renameModal"/g, 'id="' + modalId + '"');
            
            $('body').append(modifiedResponse);
            
            setTimeout(() => {
                const renameModalEl = document.getElementById(modalId);
                if (renameModalEl) {
                    const renameModal = new bootstrap.Modal(renameModalEl, {
                        backdrop: true,
                        keyboard: true
                    });
                    renameModal.show();
                    
                    renameModalEl.addEventListener('hidden.bs.modal', function() {
                        setTimeout(() => {
                            if (renameModalEl && renameModalEl.parentNode) {
                                renameModalEl.parentNode.removeChild(renameModalEl);
                            }
                        }, 100);
                    });
                }
            }, 100);
        }).fail(function() {
            console.error('Rename modal yüklenemedi');
        });
    });

    
    $(context).off('click.picker-update').on('click.picker-update', '.btn-update', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        let url = $(this).attr('href');
        let modalId = 'modal-update-' + Date.now();
        
        $.get(url, function(response) {
            $('.modal[id^="modal-update"]').remove();
            
            let modifiedResponse = response.replace(/id="updateModal"/g, 'id="' + modalId + '"');
            
            $('body').append(modifiedResponse);
            
            setTimeout(() => {
                const updateModalEl = document.getElementById(modalId);
                if (updateModalEl) {
                    const updateModal = new bootstrap.Modal(updateModalEl, {
                        backdrop: true,
                        keyboard: true
                    });
                    updateModal.show();
                    
                    updateModalEl.addEventListener('hidden.bs.modal', function() {
                        setTimeout(() => {
                            if (updateModalEl && updateModalEl.parentNode) {
                                updateModalEl.parentNode.removeChild(updateModalEl);
                            }
                        }, 100);
                    });
                }
            }, 100);
        }).fail(function() {
            console.error('Update modal yüklenemedi');
        });
    });

    
    $(context).off('click.picker-newfolder').on('click.picker-newfolder', '.btn-new-folder', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        let url = $(this).attr('href');
        let modalId = 'newFolderModal-' + Date.now();
        
        $.get(url, function(response) {
            $('.modal[id^="newFolderModal"]').remove();
            
            let modifiedResponse = response.replace(/id="newFolderModal"/g, 'id="' + modalId + '"');
            
            $('body').append(modifiedResponse);
            
            setTimeout(() => {
                const newFolderModalEl = document.getElementById(modalId);
                if (newFolderModalEl) {
                    const newFolderModal = new bootstrap.Modal(newFolderModalEl, {
                        backdrop: true,
                        keyboard: true
                    });
                    newFolderModal.show();
                    
                    
                    bindNewFolderModalEvents(modalId);
                    
                    newFolderModalEl.addEventListener('hidden.bs.modal', function() {
                        setTimeout(() => {
                            if (newFolderModalEl && newFolderModalEl.parentNode) {
                                newFolderModalEl.parentNode.removeChild(newFolderModalEl);
                            }
                        }, 100);
                    });
                }
            }, 100);
        }).fail(function() {
            console.error('New folder modal yüklenemedi');
        });
    });

   
    $(document).off('hidden.bs.modal.widget').on('hidden.bs.modal.widget', '.modal', function () {
        if (this.id !== 'file-picker-modal') {
            setTimeout(() => {
                if (this && this.parentNode) {
                    this.parentNode.removeChild(this);
                }
            }, 100);
        }
    });
};


const bindNewFolderModalEvents = function(modalId) {
    const modalSelector = '#' + modalId;
    
    $(document).off('click.createFolder-' + modalId).on('click.createFolder-' + modalId, modalSelector + ' #createFolderButton', function(e) {
        e.preventDefault();
        
        const form = $(modalSelector + ' #newFolderForm');
        const formData = new FormData(form[0]);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                cleanupModal(modalId);
                
                if (typeof window.refreshFilePicker === 'function') {
                    window.refreshFilePicker();
                } else {
                    $.pjax.reload('#file-picker-pjax', {
                        timeout: 30000
                    });
                }
                
                if (response.success) {
                    console.log('Klasör başarıyla oluşturuldu.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Klasör oluşturma hatası:', error);
                alert('Klasör oluşturulurken bir hata oluştu.');
            }
        });
    });
    
    $(document).off('click.closeNewFolder-' + modalId).on('click.closeNewFolder-' + modalId, modalSelector + ' .btn-danger, ' + modalSelector + ' .close', function() {
        cleanupModal(modalId);
    });
};


if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName, isPicker = true, attributes = ['id_storage']) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;
        window.isPicker = isPicker;
        window.currentAttributes = Array.isArray(attributes) ? attributes : [attributes];

        cleanupModal();

        $.ajax({
            url: '/storage/default/picker-modal',
            type: 'GET',
            data: {
                id: id,
                multiple: multiple,
                isJson: isJson,
                fileExtensions: window.fileExtensions,
                isPicker: isPicker,
                attributes: window.currentAttributes
            },
            success: function(response) {
                $('#file-picker-modal').remove();
                $('body').append(response);

                requestAnimationFrame(() => {
                    const modalEl = document.getElementById('file-picker-modal');
                    if (modalEl) {
                        const modal = new bootstrap.Modal(modalEl, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        
                        modal.show();
                        
                        modalEl.addEventListener('shown.bs.modal', function() {
                            updateFileCard(id_storage);
                            bindModalButtons();
                            bindFileActions();
                            forceReflow();
                        });
                    }
                });
            },
            error: function() {
                alert('Modal yüklenirken bir hata oluştu.');
            }
        });
    };
}


if (!window.refreshFilePicker) {
    window.refreshFilePicker = function() {
        const pickerContainer = $('#file-picker-modal .files-container');
        if (pickerContainer.length) {
            $.ajax({
                url: '/storage/default/picker-content',
                type: 'GET',
                data: {
                    fileExtensions: window.fileExtensions,
                    isPicker: window.isPicker,
                    attributes: window.currentAttributes
                },
                success: function(response) {
                    pickerContainer.html(response);
                    bindFileActions();
                    forceReflow();
                },
                error: function() {
                    console.error('File picker yenilenemedi.');
                }
            });
        }
    };
}


if (!window.getAttributesFromDOM) {
    window.getAttributesFromDOM = function(id) {
        let el = document.querySelector('[data-id="' + id + '"]');
        if (!el) return {};
        try {
            let attributesStr = el.getAttribute('data-attributes') || el.getAttribute('attributes');
            return attributesStr ? JSON.parse(attributesStr) : {};
        } catch (e) {
            console.error('JSON parse hatası:', e);
            return {};
        }
    }
}


if (!window.saveSelect) {
    window.saveSelect = function() {
        let attributes = window.currentAttributes && Array.isArray(window.currentAttributes) ? window.currentAttributes : ['id_storage'];
        let value;

        let selectedFiles = window.multiple ?
            $('.file-card input[type="checkbox"]:checked').map(function() {
                return $(this).closest('.file-card').data('id');
            }).get() :
            $('.file-card.active').data('id');
            
        if (window.isJson) {
            if (window.multiple) {
                value = JSON.stringify(selectedFiles.map(id => {
                    let fullData = getAttributesFromDOM(id);
                    let obj = {};
                    attributes.forEach(attr => {
                        obj[attr] = fullData[attr] || null;
                    });
                    return obj;
                }));
            } else {
                let fullData = getAttributesFromDOM(selectedFiles);
                if (attributes.length === 1) {
                    value = JSON.stringify(fullData[attributes[0]] || null);
                } else {
                    let obj = {};
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

        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            window[window.callbackName](selectedFiles);
        }

        cleanupModal();
    };
}
JS;

        $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
    }
}
?>