<?php
    use yii\widgets\ListView;
?>

<?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_file',
        'viewParams' => [
            'view' => 1,
            'attributes' => $attributes,
            'isJson' => $isJson,
            'widgetName' => $widgetName
        ],
        'options' => [
            'tag' => 'div',
            'class' => 'row',
            'style' => 'overflow-y: auto; height:450px;',
        ],
        'itemOptions' => 
        function ($model, $key, $index, $widget) use ($attributes, $isJson, $widgetName) {
            if (isset($attributes)) {
                if (is_array($attributes)) {
                    if (in_array('id_storage', $attributes)) {
                    }else{
                        $attributes[] = 'id_storage';
                    }
                }
            }
            return [
                'tag' => 'div',
                'class' => 'col-lg-3 col-sm-4 col-md-3',
                'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]],
                //'onclick' => 'selectItem(this, "' . $name . '")',
            ];
        },
        'summary' => false,
        'layout' => '{items}<div class="clearfix"></div>',
        
    ]); ?>

