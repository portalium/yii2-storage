<?php

use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
use yii\bootstrap5\Modal;
use portalium\theme\widgets\Button;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\Storage */
/* @var $form yii\widgets\ActiveForm */
?>

<?php

Modal::begin([
    'id' => 'uploadModal',
    'title' => Module::t('Upload File'),
    'options' => ['class' => 'fade'],
    'bodyOptions' => ['class' => 'modal-body'],
    'footer' => Button::widget([
            'label' => Module::t('Close'),
            'options' => [
                'class' => 'btn btn-danger',
                'data-bs-dismiss' => 'modal',
            ],
        ]) . ' ' . Html::submitButton(
            Html::tag('i', '', ['class' => 'fa fa-cloud-upload-alt']) . ' ' . Module::t('Upload'),
            ['class' => 'btn btn-success', 'form' => 'uploadForm']
        ),
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'closeButton' => false,
]);

$form = ActiveForm::begin([
    'id' => 'uploadForm',
    'action' => ['default/create'],
    'options' => ['enctype' => 'multipart/form-data'],
]);


echo $form->field($model, 'title')->textInput(['placeholder' => Module::t('Enter title..'), 'required' => true]);
echo $form->field($model, 'file')->fileInput();

ActiveForm::end();

Modal::end();

?>