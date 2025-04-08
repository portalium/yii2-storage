<?php

use portalium\storage\bundles\FilePickerAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;

FilePickerAsset::register($this);

/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var $model portalium\storage\models\Storage */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

?>
<?php

echo Html::beginTag('span', ['class' => 'col-md-4 d-flex gap-2']);
echo Html::tag(
    'span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'placeholder' => Module::t('Search file..')
    ]) .
    Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), ['class' => 'input-group-text']),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload'), ['class' => 'ms-2']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#uploadModal',
        'onclick' => 'openUploadModal()',
    ],
]);

echo Html::endTag('span');

?>
<br />
<?php
Pjax::begin([
    'id' => 'upload-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'list-file-pjax',
]);
echo $this->render('_file-list', [
'dataProvider' => $dataProvider,
]);

Pjax::end();

Pjax::begin([
    'id' => 'rename-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);
Pjax::end();

Pjax::begin([
    'id' => 'update-file-pjax',
]);
Pjax::end();

Pjax::begin([
    'id' => 'share-file-pjax',
]);
Pjax::end();

?>

<?php

$this->registerJs(
    <<<JS
    function openRenameModal(id) {
        event.preventDefault();
        $.pjax.reload({
            container: '#rename-file-pjax',
            type: 'GET',
            url: '/storage/default/rename-file',
            data: { id: id },
        }).done(function() {
            setTimeout(function() {
                $('#renameModal').modal('show');
            }, 1);
        }).fail(function(e) {
            console.log(e);
        });
    }
    JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
    function openUploadModal() {
        event.preventDefault();
        $.pjax.reload({
            container: '#upload-file-pjax',
            type: 'GET',
            url: '/storage/default/upload-file',
        }).done(function() {
            setTimeout(function() {
                $('#uploadModal').modal('show');
            }, 1);
        }).fail(function(e) {
            console.log(e);
        });
    }
    $(document).on('click', '#uploadButton', function(e) {
        e.preventDefault();
        $('#uploadForm').submit();
    });
    JS,
    \yii\web\View::POS_END
);
?>
