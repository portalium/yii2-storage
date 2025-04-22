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
        Yii::$app->view->registerCss(<<<CSS
        .file-card.active {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 2px solid #007bff;
            transform: scale(1.05);
            transition: all 0.3s ease;
        }
        CSS);
        parent::init();
    }

    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
        }

        echo Html::button(Module::t('Select File'), [
            'class' => 'btn btn-primary',
            'onclick' => 'openFilePickerModal("' . $this->options['id'] . '", "' . json_decode($this->model->{$this->attribute}, true)['id_storage'] . '")'
        ]);

        Pjax::begin([
            'id' => $this->options['id'] . '-pjax',
            'enablePushState' => false,
            'timeout' => 50000,
        ]);
        
        $js = <<< JS

        if (window.openFilePickerModal === undefined) {
            window.openFilePickerModal = function (id, id_storage) {
                if ($('#file-picker-modal').length === 0) {
                    $.pjax.reload({
                        container: '#' + id + '-pjax',
                        url: '/storage/default/picker-modal',
                        type: 'GET',
                        data: {
                            id: id
                        },
                    }).done(function () {
                        $('.file-card.active').removeClass('active');
                        $('.file-card input[type="checkbox"]').prop('checked', false);
                        $('#file-picker-modal span[data-id="' + id_storage + '"]').addClass('active');
                        $('#file-picker-modal span[data-id="' + id_storage + '"] input[type="checkbox"]').prop('checked', true);
                        setTimeout(function () {
                            var modal = new bootstrap.Modal(document.getElementById('file-picker-modal')); // Initialize Bootstrap modal
                            modal.show();
                            window.inputId = id;
                        }, 500);
                    });
                } else {
                    $('.file-card.active').removeClass('active');
                    $('.file-card input[type="checkbox"]').prop('checked', false);
                    $('#file-picker-modal span[data-id="' + id_storage + '"]').addClass('active');
                    $('#file-picker-modal span[data-id="' + id_storage + '"] input[type="checkbox"]').prop('checked', true);
                    setTimeout(function () {
                        var modal = new bootstrap.Modal(document.getElementById('file-picker-modal')); // Initialize Bootstrap modal
                        modal.show();
                        window.inputId = id;
                    }, 500);
                }
            };
        }

        if (window.selectFile === undefined) {
            window.selectFile = function (element, id_storage) {
                $('.file-card.active').removeClass('active');
                if ($(element).is(':checked')) {
                    $('.file-card input[type="checkbox"]').not(element).prop('checked', false);
                    $('.file-card[data-id="' + id_storage + '"]').addClass('active');
                } else {
                    $('.file-card[data-id="' + id_storage + '"]').removeClass('active');
                }
            };
        }

        if (window.saveSelect === undefined) {
            window.saveSelect = function () {
                var selectedFile = $('.file-card.active').data('id');
                $('#' + window.inputId).val(JSON.stringify({id_storage: selectedFile}));
                $('#file-picker-modal').modal('hide');
            };
        }
        JS;
        $this->view->registerJs($js, \yii\web\View::POS_BEGIN);
        Pjax::end();
    }
}
