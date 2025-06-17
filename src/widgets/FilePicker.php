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

        // attributes özelliğini çeşitli kaynaklardan al
        if (isset($this->options['attributes'])) {
            // 1. Öncelik: options'dan
            $this->attributes = $this->options['attributes'];
        } elseif (isset($_GET['attributes'])) {
            // 2. Öncelik: GET parametresinden
            $this->attributes = is_string($_GET['attributes']) ?
                explode(',', $_GET['attributes']) : $_GET['attributes'];
        } elseif (isset($_POST['attributes'])) {
            // 3. Öncelik: POST parametresinden
            $this->attributes = is_string($_POST['attributes']) ?
                explode(',', $_POST['attributes']) : $_POST['attributes'];
        }
        // 4. Son olarak varsayılan değer zaten tanımlı

        // attributes'un array olduğundan emin ol
        if (!is_array($this->attributes)) {
            $this->attributes = [$this->attributes];
        }

        // Boş veya geçersiz attributes'u temizle
        $this->attributes = array_filter($this->attributes, function ($attr) {
            return !empty(trim($attr));
        });

        // Eğer hiç geçerli attribute kalmadıysa varsayılan değeri kullan
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

        // JavaScript global değişkenlerini ayarla
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
        // Belirli bir modalı kapat
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
            // Modal elementini DOM'dan kaldır
            setTimeout(() => {
                if (modalEl && modalEl.parentNode) {
                    modalEl.parentNode.removeChild(modalEl);
                }
            }, 300);
        }
    } else {
        // Ana file picker modalını kapat
        const modalEl = document.getElementById('file-picker-modal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
    }
    
    // Backdrop'ları temizle
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
    $(document).off('click', '.btn-share').on('click', '.btn-share', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url, function(response) {
            $('body').append(response);
            const modal = new bootstrap.Modal(document.getElementById('modal-share'));
            modal.show();
        });
    });

    $(document).off('click', '.btn-rename').on('click', '.btn-rename', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url, function(response) {
            $('body').append(response);
            const modal = new bootstrap.Modal(document.getElementById('modal-rename'));
            modal.show();
        });
    });

    $(document).off('click', '.btn-update').on('click', '.btn-update', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url, function(response) {
            $('body').append(response);
            const modal = new bootstrap.Modal(document.getElementById('modal-update'));
            modal.show();
        });
    });

    // New Folder butonuna özel event listener
    $(document).off('click', '.btn-new-folder').on('click', '.btn-new-folder', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.get(url, function(response) {
            $('body').append(response);
            const modal = new bootstrap.Modal(document.getElementById('newFolderModal'));
            modal.show();
            
            // New folder modal için özel event bindings
            bindNewFolderModalEvents();
        });
    });

    // Genel modal kapatma eventi
    $(document).off('hidden.bs.modal.widget').on('hidden.bs.modal.widget', '.modal', function () {
        // Sadece child modalları kaldır, ana file-picker-modal'ı koruy
        if (this.id !== 'file-picker-modal') {
            $(this).remove();
        }
    });
};

const bindNewFolderModalEvents = function() {
    // Create Folder butonu için event
    $(document).off('click.createFolder').on('click.createFolder', '#createFolderButton', function(e) {
        e.preventDefault();
        
        const form = $('#newFolderForm');
        const formData = new FormData(form[0]);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Modalı kapat
                cleanupModal('newFolderModal');
                
                // File picker'ı yenile
                if (typeof window.refreshFilePicker === 'function') {
                    window.refreshFilePicker();
                } else {
                    // Manuel olarak sayfayı yenile veya pjax ile güncelle
                    $.pjax.reload('#file-picker-pjax', {
                        timeout: 30000
                    });
                }
                
                // Başarı mesajı göster
                if (response.success) {
                    alert('Klasör başarıyla oluşturuldu.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Klasör oluşturma hatası:', error);
                alert('Klasör oluşturulurken bir hata oluştu.');
            }
        });
    });
    
    // Close butonları için event
    $(document).off('click.closeNewFolder').on('click.closeNewFolder', '#newFolderModal .btn-danger, #newFolderModal .close', function() {
        cleanupModal('newFolderModal');
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
                    const modal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    document.body.classList.add('modal-open');
                    document.body.style.paddingRight = '0px';
                    modal.show();

                    updateFileCard(id_storage);
                    bindModalButtons();
                    bindFileActions();
                    forceReflow();
                });
            },
            error: function() {
                alert('Modal yüklenirken bir hata oluştu.');
            }
        });
    };
}

// File picker'ı yenileme fonksiyonu
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
    //
}
