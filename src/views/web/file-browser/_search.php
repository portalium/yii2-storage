<?php

use portalium\storage\models\Storage;
use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageSearch */
/* @var $form yii\widgets\ActiveForm */
?>



<?php $form = ActiveForm::begin([
    'action' => [Yii::$app->controller->action->id == 'manage' ? 'manage' : 'index'],
    'id' => 'storage-search-form-' . $name,
    'method' => 'get',
    'options' => [
        'data-pjax' => 1,
        'style' => 'width: 100%;'
    ],

]); ?>

<div class="form-group" style="display: flex;justify-content: space-between;">
    <?= Html::beginTag('div', ['class' => 'd-flex']); ?>
    <?= $form->field($model, 'title', ['options' => ['style' => 'margin-bottom:0px !important;width: 150px; margin-right: 10px;', 'class' => '']])->label(false)->textInput(['placeholder' => Module::t('Search for file...'), 'style'=>'width: 150px;']) ?>
    <?= Html::button(Module::t(''), ['class' => 'fa fa-search btn btn-success', 'id' => 'storage-search-button-' . $name]) ?>
    <?= Html::endTag('div'); ?>
    <?= $form->field($model, 'access', ['options' => ['style' => 'margin-bottom:0px !important;width: 150px; margin-right: 10px;', 'id' => 'storage-search-form-access-' . $name, 'class' => '']])->dropDownList(Storage::getAccesses(), ['prompt' => Module::t('All'), 'style'=>'width: 150px;'])->label(false) ?>
</div>

<?php ActiveForm::end(); ?>



<?php

$this->registerJs(
    "
    $('#storage-search-button-$name').click(function(){
        $.pjax.reload({container: '#file-picker-pjax$name', data: $('#storage-search-form-$name').serialize(), url: '" . ($isPicker ? '/storage/file-browser/index' : '/storage/default/manage' ). "'});
    });
    $('#storage-search-form-access-$name').change(function(){
        $.pjax.reload({container: '#file-picker-pjax$name', data: $('#storage-search-form-$name').serialize(), url: '" . ($isPicker ? '/storage/file-browser/index' : '/storage/default/manage') . "'});
    });
"
);
