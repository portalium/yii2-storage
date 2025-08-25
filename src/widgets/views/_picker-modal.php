    <?php

    use portalium\storage\Module;
    use portalium\theme\widgets\Html;
    use portalium\theme\widgets\Modal;

    /* @var $dataProvider yii\data\ActiveDataProvider */

Yii::$app->view->registerCss("
    #file-picker-modal .panel-footer {
        border-top: none !important;
    }

    .file-manager {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        max-width: 100%;
    }
    .file-controls {
        position: sticky;
        top: 0;
        background: white;
        z-index: 1;
        padding: 10px 15px;
        flex-shrink: 0;
        border-bottom: 1px solid #e9ecef;
        overflow: visible;
        max-width: 100%;
    }
    .folders-section {
        flex-shrink: 0;
        padding: 0px 15px;
        max-width: 100%;
        overflow: visible;
    }
    .files-section {
        flex: 1;
        padding: 0px 15px;
        max-width: 100%;
        min-height: 0;
        overflow: visible;
        display: flex;
        flex-direction: column;
    }
    #folder-list {
        overflow-y: auto ;
        overflow-x: hidden ;
        height: 150px ;
    }
    #file-list {
        overflow-y: auto ;
        overflow-x: hidden ;
        height: 400px;
    }
        
    .file-select-checkbox{
        flex-shrink: 0; /* checkbox küçülmesin */
    }
");

Modal::begin([
    'title' => Module::t('Select File'),
    'id' => 'file-picker-modal',
    'size' => Modal::SIZE_LARGE,
    'footer' =>
        Html::button(Module::t('Close'), [
            'class' => 'btn btn-danger filepicker-close',
            'data-bs-dismiss' => 'modal',
        ]) .
        Html::button(Module::t('Select'), [
            'class' => 'btn btn-success btn-select',
            'onclick' => 'saveSelect()',
        ]),
]);

echo $this->render('@portalium/storage/views/web/default/index', [
    'fileDataProvider' => $dataProvider,
    'directoryDataProvider' => $directoryDataProvider,
    //'directories' => $directories,
    //'files'  => $files,
    //'pagination'  => $pagination,
    'isPicker' => true,
]);

Modal::end();
