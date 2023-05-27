<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;


echo $this->render('_form', [
    'model' => $model,
]);


$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . '/'. Yii::$app->setting->getValue('storage::path') .'/';
if (isset($attributes)) {
    if (is_array($attributes)) {
        if (in_array('id_storage', $attributes)) {
        }else{
            $attributes[] = 'id_storage';
        }
    }
}
?>

<?php Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 200px; display: block; overflow: hidden;'],
    'actions' => [
        'header' => ($view == 1) ? [
            Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-form-modal-' . $model->id_storage]),
            ($isPicker) ? Html::tag('i', '', ['class' => 'fa fa-check btn btn-success', 'name' => 'checkedItems[]', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "selectItem(this, '" . $widgetName . "')"]) : '',
            Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'name' => 'removeItem', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "removeItem(this, '" . $widgetName . "')"]),
        ] : [],
        'footer' => [
            Html::tag("div",(strlen($model->title) > 25) ? substr(str_replace("’","´",$model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) ?>

<?php 
    if(isset(Storage::getMimeTypeList()[$model->mime_type])){
        $mimeType = Storage::getMimeTypeList()[$model->mime_type];
    }else{
        $mimeType = "other";
    }
    $mime = explode('/', $mimeType)[0];
    if ($mime == 'image') {
        echo Html::img(Html::encode($path . $model->name),['style' => ' 
        object-fit: cover;
        width: 100%;
        height: 177px;
        object-position: center 40%;
        padding-top: 20px;
    ']);
    } elseif ($mime == 'video') {
        echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
    } elseif ($mime == 'audio') {
        echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
    } else {
        echo Html::tag('i', '', ['class' => 'fa fa-file-o']);
    }
?>
<?php Panel::end() ?>