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
$path = Url::base() . Yii::$app->setting->getValue('app::data');
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

<?php Panel::end() ?>
