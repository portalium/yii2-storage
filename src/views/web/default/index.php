<?php

use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\GridView;
use portalium\theme\widgets\ActionColumn;
use portalium\storage\Module;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\widgets\FilePicker;
/* @var $this yii\web\View */
/* @var $searchModel portalium\storage\models\StorageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="storage-index">

    <?= FilePicker::widget([
        'name' => 'file',
        'isPicker' => false,
        'manage' => $manage
    ]) ?>


</div>
