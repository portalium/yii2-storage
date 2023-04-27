<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use yii2assets\pdfjs\PdfJs;
use portalium\storage\Module;
use diginova\media\models\Media;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;

$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . '/'. Yii::$app->setting->getValue('storage::path') .'/';
?>
<?php Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 200px; display: block;'],
    'actions' => [
        'header' => [],
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
        echo Html::img(Html::encode($path . $model->name), ['width' => '100%', 'height' => '100%']);
    } elseif ($mime == 'video') {
        echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
    } elseif ($mime == 'audio') {
        echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
    } else {
        echo Html::tag('i', '', ['class' => 'fa fa-file-o']);
    }
?>
<?php Panel::end() ?>
