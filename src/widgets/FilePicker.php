<?php
namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\InputWidget;
use yii\data\ActiveDataProvider;

class FilePicker extends InputWidget
{
    public $dataProvider;
    public $multiple = 0;
    public $isJson = 1;
    public $callbackName = null;
    public $fileExtensions = null;
    public $manage = false;
    
    public function init()
    {
        parent::init();
        Yii::$app->view->registerJs('$.pjax.defaults.timeout = 30000;');

        if (isset($this->options['multiple'])) {
            $this->multiple = $this->options['multiple'];
        }

        if (isset($this->options['isJson'])) {
            $this->isJson = $this->options['isJson'];
        }

        if (isset($this->options['callbackName'])) {
            $this->callbackName = $this->options['callbackName'];
        }

        if (isset($this->options['fileExtensions'])) {
            $this->fileExtensions = $this->options['fileExtensions'];
        }
    }
    
    public function run()
{
    $query = Storage::find();
    if ($this->fileExtensions) {
        foreach ($this->fileExtensions as $ext) {
            $query->orWhere(['like', 'name', $ext]);
        }
    }

    $this->dataProvider = new ActiveDataProvider([
        'query' => $query,
        'pagination' => [
            'pageSize' => 12, 
        ],
    ]);

    if ($this->hasModel()) {
        echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
    }

    $value = $this->model->{$this->attribute} ?? '';
    $decoded = [];

    if (!empty($value)) {
        $decoded = json_decode($value, true);
    }

    if ($this->multiple && is_array($decoded)) {
        $first = reset($decoded);
        $idStorage = is_array($first) ? ($first['id_storage'] ?? '') : $first;
    } else {
        $idStorage = is_array($decoded) ? ($decoded['id_storage'] ?? '') : $decoded;
    }

    echo Html::button(Module::t('Select File'), [
        'class' => 'btn btn-primary',
        'onclick' => 'openFilePickerModal("' . $this->options['id'] . '", "' . $idStorage . '", ' . $this->multiple . ', ' . $this->isJson . ', "' . ($this->callbackName ?? '') . '")'
    ]);

    Pjax::begin([
        'id' => $this->options['id'] . '-pjax',
        'enablePushState' => false,
        'timeout' => 50000,
    ]);

    $js = <<<JS
const updateFileCard = function(id_storage) {
    $('.file-card.active').removeClass('active');
    $('.file-card input[type="checkbox"]').prop('checked', false);

    if (id_storage) {
        if (Array.isArray(id_storage)) {
            id_storage.forEach(function(id) {
                $('#file-picker-modal span[data-id="' + id + '"]').addClass('active');
                $('#file-picker-modal span[data-id="' + id + '"] input[type="checkbox"]').prop('checked', true);
            });
        } else {
            $('#file-picker-modal span[data-id="' + id_storage + '"]').addClass('active');
            $('#file-picker-modal span[data-id="' + id_storage + '"] input[type="checkbox"]').prop('checked', true);
        }
    }
};

const showModal = function(id) {
    setTimeout(function () {
        var modal = new bootstrap.Modal(document.getElementById('file-picker-modal'));
        modal.show();
        window.inputId = id;
        
        
        $(document).on('click', '#file-picker-modal .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            
            $.pjax.reload({
                container: '#' + id + '-pjax',
                url: url,
                type: 'GET',
                data: { 
                    id: id,
                    multiple: window.multiple,
                    isJson: window.isJson
                },
                push: false,
                replace: false
            }).done(function() {
                
                setTimeout(function() {
                    let modal = new bootstrap.Modal(document.getElementById('file-picker-modal'));
                    modal.show();
                    
                    
                    if (window.multiple) {
                        let selectedIds = [];
                        $('#' + window.inputId).val().then(function(val) {
                            if (val && window.isJson) {
                                let parsed = JSON.parse(val);
                                selectedIds = parsed.map(item => item.id_storage);
                            } else if (val) {
                                selectedIds = val.split(',');
                            }
                            updateFileCard(selectedIds);
                        });
                    } else {
                        let selectedId = null;
                        $('#' + window.inputId).val().then(function(val) {
                            if (val && window.isJson) {
                                let parsed = JSON.parse(val);
                                selectedId = parsed.id_storage;
                            } else {
                                selectedId = val;
                            }
                            updateFileCard(selectedId);
                        });
                    }
                }, 200);
            });
        });
    }, 500);
};

if (window.openFilePickerModal === undefined) {
    window.openFilePickerModal = function (id, id_storage, multiple, isJson, callbackName) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        
        
        $(document).off('click', '#file-picker-modal .pagination a');

        if ($('#file-picker-modal').length === 0) {
            $.pjax.reload({
                container: '#' + id + '-pjax',
                url: '/storage/default/picker-modal',
                type: 'GET',
                data: { 
                    id: id,
                    multiple: multiple,
                    isJson: isJson
                }
            }).done(function () {
                updateFileCard(id_storage);
                showModal(id);
            });
        } else {
            updateFileCard(id_storage);
            showModal(id);
        }
    };
}


$(document).on('pjax:complete', function() {
    if ($('#file-picker-modal').length > 0) {
        
        if ($('#file-picker-modal').hasClass('show')) {
           
        } else {
            
        }
    }
});

if (window.saveSelect === undefined) {
    window.saveSelect = function () {
        let selectedFiles = [];
        let selectedFile = null;

        if (window.multiple) {
            $('.file-card input[type="checkbox"]:checked').each(function () {
                selectedFiles.push($(this).closest('.file-card').data('id'));
            });

            if (window.isJson) {
                var jsonResult = selectedFiles.map(function(id) {
                    return { id_storage: id };
                });
                $('#' + window.inputId).val(JSON.stringify(jsonResult));
            } else {
                $('#' + window.inputId).val(selectedFiles.join(','));
            }
        } else {
            selectedFile = $('.file-card.active').data('id');

            if (window.isJson) {
                $('#' + window.inputId).val(JSON.stringify({id_storage: selectedFile}));
            } else {
                $('#' + window.inputId).val(selectedFile);
            }
        }

        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            if (window.multiple) {
                window[window.callbackName](selectedFiles);
            } else {
                window[window.callbackName](selectedFile);
            }
        }

        $('#file-picker-modal').modal('hide');
    };
}
JS;

    $this->view->registerJs($js, \yii\web\View::POS_BEGIN);

    Pjax::end();
}
}