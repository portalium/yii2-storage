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
        $this->attributes = array_filter($this->attributes, function($attr) {
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

const cleanupModal = function() {
    const modalEl = document.getElementById('file-picker-modal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
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

if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName, isPicker = true, attributes = ['id_storage']) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;
        window.isPicker = isPicker;
        window.currentAttributes = Array.isArray(attributes) ? attributes : [attributes]; // Fonksiyon parametresinden al

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
                    forceReflow();
                });

                // PJAX pagination event'i
                $(document).off('click.pjax-pagination').on('click.pjax-pagination', '#file-picker-modal .pagination a', function(e) {
                    e.preventDefault();
                    $('#file-picker-modal .modal-content').append('<div class="loading-overlay"><div class="spinner"></div></div>');

                    $.ajax({
                        url: $(this).attr('href'),
                        type: 'GET',
                        data: {
                            id: window.inputId,
                            multiple: window.multiple,
                            isJson: window.isJson,
                            fileExtensions: window.fileExtensions,
                            isPicker: window.isPicker,
                            attributes: window.currentAttributes
                        },
                        success: function(newContent) {
                            const \$temp = $('<div></div>').append(newContent);
                            const newGrid = \$temp.find('.files-container').html();
                            const newPagination = \$temp.find('.pagination-container').html();
                            $('#file-picker-modal .files-container').html(newGrid);
                            $('#file-picker-modal .pagination-container').html(newPagination);
                            $('.loading-overlay').remove();
                            updateFileCard(id_storage);
                            forceReflow();
                        },
                        error: function() {
                            $('.loading-overlay').remove();
                            alert('Sayfa yüklenirken bir hata oluştu.');
                        }
                    });
                });

                // Search input binding
                $(document).off('keyup.picker-search').on('keyup.picker-search', '#file-picker-modal #searchFileInput', function() {
                    let searchTimer;
                    clearTimeout(searchTimer);
                    const q = $(this).val().trim();
                    const fileExtensions = Array.isArray(window.fileExtensions) ? window.fileExtensions.join(',') : '';
                    let finalUrl = '/storage/default/search?q=' + encodeURIComponent(q) + '&isPicker=' + (window.isPicker ? '1' : '0');
                    
                    if (fileExtensions) {
                        finalUrl += '&fileExtensions=' + encodeURIComponent(fileExtensions);
                    }
                    
                    searchTimer = setTimeout(function() {
                        $.ajax({
                            url: finalUrl,
                            type: 'GET',
                            data: {
                                id: window.inputId,
                                multiple: window.multiple,
                                isJson: window.isJson,
                                fileExtensions: window.fileExtensions,
                                isPicker: window.isPicker,
                                attributes: window.currentAttributes
                            },
                            success: function(newContent) {
                                const \$temp = $('<div></div>').append(newContent);
                                const newGrid = \$temp.find('.files-container').html();
                                const newPagination = \$temp.find('.pagination-container').html();
                                $('#file-picker-modal .files-container').html(newGrid);
                                $('#file-picker-modal .pagination-container').html(newPagination);
                                updateFileCard(id_storage);
                                forceReflow();
                            }
                        });
                    }, 500);
                });

                // Folder açma işlemi
                $(document).off('click.picker-folder').on('click.picker-folder', '#file-picker-modal .folder-item', function(e) {
                    if ($(e.target).closest('.dropdown').length) return;
                    
                    const folderId = $(this).data('id');
                    let url = '/storage/default/picker-modal';
                    
                    if (folderId) {
                        url += '?id_directory=' + folderId;
                    }
                    
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            id: window.inputId,
                            multiple: window.multiple,
                            isJson: window.isJson,
                            fileExtensions: window.fileExtensions,
                            isPicker: window.isPicker,
                            id_directory: folderId || null,
                            attributes: window.currentAttributes
                        },
                        success: function(newContent) {
                            const \$temp = $('<div></div>').append(newContent);
                            const newGrid = \$temp.find('.files-container').html();
                            const newPagination = \$temp.find('.pagination-container').html();
                            $('#file-picker-modal .files-container').html(newGrid);
                            $('#file-picker-modal .pagination-container').html(newPagination);
                            updateFileCard(id_storage);
                            forceReflow();
                        }
                    });
                });
            },
            error: function() {
                alert('Modal yüklenirken bir hata oluştu.');
            }
        });
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
        // attributes'u fonksiyon parametresinden gelen değerden al
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