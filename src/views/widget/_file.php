<?php

use portalium\theme\widgets\Modal;
use yii\helpers\Url;
use yii\helpers\Html;
use yii2assets\pdfjs\PdfJs;
use portalium\storage\Module;
use diginova\media\models\Media;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Panel;

$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . Yii::$app->setting->getValue('app::data');
?>
<?php Panel::begin([
    'title' => (strlen($model->title) > 25) ? substr(str_replace("’","´",$model->title), 0, 25) . '...' : Html::encode($model->title),
    'bodyOptions' => ['style' => 'height: 200px; display: block;'],
    'footerOptions' => ['style' => 'height: 45px; display: block;'],
    'actions' => [
        'header' => [
            Html::tag('i', '', ['class' => 'fa fa-check btn btn-success', 'name' => 'checkedItems[]', 'data' => json_encode($model->getAttributes())]),
        ],
    ]
]) ?>

<?php 

    if (in_array($model->mime_type, Storage::MIME_TYPE['image'])) {
        echo Html::img(Html::encode($path . $model->name), ['width' => '100%', 'height' => '100%']);
    } elseif (in_array($model->mime_type, Storage::MIME_TYPE['video'])) {
        echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
    } elseif (in_array($model->mime_type, Storage::MIME_TYPE['audio'])) {
        echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
    } else {
        echo Html::tag('i', '', ['class' => 'fa fa-file-o']);
    }
?>
<?php Panel::end() ?>

