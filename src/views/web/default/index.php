<?php

use yii\helpers\Html;
use portalium\storage\Module;
use portalium\theme\widgets\Button;

/* @var $this yii\web\View */
/* @var $searchModel portalium\storage\models\StorageSearch */
/* @var $model portalium\storage\models\Storage */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php

echo Html::beginTag('span', ['class' => 'col-md-4 d-flex gap-2']);
echo Html::tag('span',
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
    ],
]);

echo Html::endTag('span');
echo $this->render('_form', ['model' => $model]);
?>

