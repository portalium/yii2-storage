<?php

namespace portalium\storage\widgets;

use Yii;
use portalium\widgets\Pjax;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\InputWidget;

class FilePicker extends InputWidget
{
    public function init()
    {
        parent::init();
    }

    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        $id_storage = $this->model->{$this->attribute} ?? '';

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'openFilePickerModal("' . $this->options['id'] . '", "' . $id_storage . '")'
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
            $('#file-picker-modal span[data-id="' + id_storage + '"]').addClass('active');
            $('#file-picker-modal span[data-id="' + id_storage + '"] input[type="checkbox"]').prop('checked', true);
        };

        const showModal = function(id) {
            setTimeout(function () {
                var modal = new bootstrap.Modal(document.getElementById('file-picker-modal')); 
                modal.show();
                window.inputId = id;
            }, 1000);
        };

        if (window.openFilePickerModal === undefined) {
            window.openFilePickerModal = function (id, id_storage) {
                if ($('#file-picker-modal').length === 0) {
                    $.pjax.reload({
                        container: '#' + id + '-pjax',
                        url: '/storage/default/picker-modal',
                        type: 'GET',
                        data: { id: id }
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

        if (window.saveSelect === undefined) {
            window.saveSelect = function () {
                var selectedFile = $('.file-card.active').data('id');
                $('#' + window.inputId).val(selectedFile); 
                $('#file-picker-modal').modal('hide');
            };
        }
        
JS;

        $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
        Pjax::end();
    }
}