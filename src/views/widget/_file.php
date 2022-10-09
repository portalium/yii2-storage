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

<?php Panel::end() ?>

