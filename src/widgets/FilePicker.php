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
// Modal yardımcı fonksiyonları
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

const cleanupModal = function(modalId = null) {
    if (modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
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
            if (modal) modal.hide();
        }
        
        // Modal kapandıktan sonra ana sayfa event'lerini yeniden bağla
        setTimeout(() => {
            restoreMainPageEvents();
        }, 500);
    }
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
};

// Ana sayfa event'lerini geri yükleme fonksiyonu
const restoreMainPageEvents = function() {
    // Ana sayfa için event'leri yeniden bağla (picker dışında)
    if (!window.isPicker || window.isPicker === false) {
        // Ana sayfa dropdown event'lerini yeniden bağla
        $(document).off('click.main-dropdown').on('click.main-dropdown', '.dropdown-toggle:not(#file-picker-modal .dropdown-toggle), .file-ellipsis:not(#file-picker-modal .file-ellipsis), .folder-ellipsis:not(#file-picker-modal .folder-ellipsis)', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = $(this).closest('.dropdown');
            const menu = dropdown.find('.dropdown-menu');
            
            // Diğer dropdown'ları kapat
            $('.dropdown-menu').not(menu).removeClass('show');
            $('.dropdown').not(dropdown).removeClass('show');
            
            // Bu dropdown'ı toggle et
            dropdown.toggleClass('show');
            menu.toggleClass('show');
        });

        // Ana sayfa dropdown item event'lerini yeniden bağla
        $(document).off('click.main-action').on('click.main-action', '.dropdown-item:not(#file-picker-modal .dropdown-item), .dropdown-menu a:not(#file-picker-modal .dropdown-menu a)', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const onclick = $(this).attr('onclick');
            const href = $(this).attr('href');
            
            // Dropdown'ları kapat
            $('.dropdown-menu').removeClass('show');
            $('.dropdown').removeClass('show');
            
            // Action'ı çalıştır
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

        // Ana sayfa dropdown dışına tıklama
        $(document).off('click.main-outside').on('click.main-outside', function(e) {
            if (!$(e.target).closest('.dropdown').length && !$(e.target).closest('#file-picker-modal').length) {
                $('.dropdown-menu').removeClass('show');
                $('.dropdown').removeClass('show');
            }
        });
        
        console.log('Ana sayfa event\'leri geri yüklendi');
    }
};

// Ana event binding fonksiyonu
const bindFilePickerEvents = function() {
    const context = '#file-picker-modal';
    
    // Modal butonları
    $(document).off('click.picker-select').on('click.picker-select', context + ' .btn-select', function() {
        window.saveSelect();
    });

    // Modal kapatma butonları - özel handling
    $(document).off('click.picker-close').on('click.picker-close', context + ' .btn-close, ' + context + ' .close, ' + context + ' [data-bs-dismiss="modal"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        cleanupModal();
    });

    // Modal'ın X butonuna özel event
    $(document).off('click.picker-dismiss').on('click.picker-dismiss', context + ' .modal-header .btn-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        cleanupModal();
    });

    // Dropdown toggle - sadece modal içinde
    $(document).off('click.picker-dropdown').on('click.picker-dropdown', context + ' .dropdown-toggle, ' + context + ' .file-ellipsis, ' + context + ' .folder-ellipsis', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const dropdown = $(this).closest('.dropdown');
        const menu = dropdown.find('.dropdown-menu');
        
        // Sadece modal içindeki diğer dropdown'ları kapat
        $(context + ' .dropdown-menu').not(menu).removeClass('show');
        $(context + ' .dropdown').not(dropdown).removeClass('show');
        
        // Bu dropdown'ı toggle et
        dropdown.toggleClass('show');
        menu.toggleClass('show');
    });

    // Dropdown item clicks - sadece modal içinde
    $(document).off('click.picker-action').on('click.picker-action', context + ' .dropdown-item, ' + context + ' .dropdown-menu a', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const action = $(this).data('action');
        const href = $(this).attr('href');
        const onclick = $(this).attr('onclick');
        const id = $(this).closest('[data-id]').data('id');
        
        // Sadece modal içindeki dropdown'ları kapat
        $(context + ' .dropdown-menu').removeClass('show');
        $(context + ' .dropdown').removeClass('show');
        
        // Action'ı çalıştır
        if (onclick) {
            try { eval(onclick); } catch(e) { console.error(e); }
        } else if (action && id) {
            handlePickerAction(action, id, href);
        } else if (href) {
            window.location.href = href;
        }
    });

    // Modal içine tıklama - sadece modal içindeki dropdown'ları etkileyecek
    $(document).off('click.picker-outside').on('click.picker-outside', context, function(e) {
        if (!$(e.target).closest(context + ' .dropdown').length) {
            $(context + ' .dropdown-menu').removeClass('show');
            $(context + ' .dropdown').removeClass('show');
        }
    });
};

// Action handler - basitleştirilmiş
const handlePickerAction = function(action, id, href) {
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
            if (href) openActionModal(action, href);
            break;
        case 'delete-folder':
            if (confirm('Klasörü silmek istediğinizden emin misiniz?')) {
                $.post('/storage/default/delete-folder', { id: id, isPicker: '1' })
                    .done(() => window.refreshFilePicker && window.refreshFilePicker());
            }
            break;
    }
};

// Modal açma - basitleştirilmiş
const openActionModal = function(action, href) {
    const modalId = 'action-modal-' + Date.now();
    
    $.get(href).done(function(response) {
        // Eski modalları temizle
        $('.modal[id*="Modal"], .modal[id*="modal-"]').remove();
        
        // Modal ID'sini güncelle
        const idMap = {
            'rename': 'renameModal',
            'update': 'updateModal', 
            'share': 'modal-share'
        };
        const oldId = idMap[action] || 'modal';
        response = response.replace(new RegExp('id="' + oldId + '"', 'g'), 'id="' + modalId + '"');
        
        $('body').append(response);
        
        setTimeout(() => {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                
                // Form submit event
                $(modalEl).find('form').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: this.action,
                        type: 'POST',
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        complete: function() {
                            cleanupModal(modalId);
                            window.refreshFilePicker && window.refreshFilePicker();
                        }
                    });
                });
                
                modalEl.addEventListener('hidden.bs.modal', () => {
                    setTimeout(() => modalEl.remove(), 100);
                });
            }
        }, 100);
    });
};

// Ana modal açma fonksiyonu
if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName, isPicker = true, attributes = ['id_storage']) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;
        window.isPicker = isPicker;
        window.currentAttributes = Array.isArray(attributes) ? attributes : [attributes];

        cleanupModal();

        $.get('/storage/default/picker-modal', {
            id: id,
            multiple: multiple,
            isJson: isJson,
            fileExtensions: window.fileExtensions,
            isPicker: isPicker,
            attributes: window.currentAttributes
        }).done(function(response) {
            $('#file-picker-modal').remove();
            $('body').append(response);

            const modalEl = document.getElementById('file-picker-modal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                
                modal.show();
                
                // Modal tamamen göründükten sonra event'leri bağla
                modalEl.addEventListener('shown.bs.modal', function() {
                    updateFileCard(id_storage);
                    bindFilePickerEvents();
                });
                
                // Modal kapandığında ana sayfa event'lerini geri yükle
                modalEl.addEventListener('hidden.bs.modal', function() {
                    setTimeout(() => {
                        restoreMainPageEvents();
                        // Modal elementi temizle
                        if (modalEl && modalEl.parentNode) {
                            modalEl.parentNode.removeChild(modalEl);
                        }
                    }, 100);
                });
            }
        });
    };
}

// Sayfa yüklendiğinde ana sayfa event'lerini bağla
$(document).ready(function() {
    restoreMainPageEvents();
});

// Yardımcı fonksiyonlar
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
                bindFilePickerEvents();
            });
        }
    };
}

if (!window.getAttributesFromDOM) {
    window.getAttributesFromDOM = function(id) {
        const el = document.querySelector('[data-id="' + id + '"]');
        if (!el) return {};
        try {
            const attr = el.getAttribute('data-attributes') || el.getAttribute('attributes');
            return attr ? JSON.parse(attr) : {};
        } catch (e) {
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
