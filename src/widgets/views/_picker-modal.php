    <?php

    use portalium\storage\Module;
    use portalium\theme\widgets\Html;
    use portalium\theme\widgets\Modal;

    /* @var $dataProvider yii\data\ActiveDataProvider */

    // Modal içi özel stil (isteğe bağlı)
    Yii::$app->view->registerCss("
        #file-picker-modal .panel-footer {
            border-top: none !important;
        }

        .file-manager {
            display: flex;
            flex-direction: column;
            height: 700px; /* Modal yüksekliğine göre ayarla */
            overflow: scroll;
        }

        .file-controls {
            overflow-y: visible;
            position: sticky; 
            top: 0;           
            background: white; 
            z-index: 1;   
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            flex-shrink: 0;   
        }
        .file-list {
            flex: 1;          /* Kalan tüm alanı kapla */
            overflow-y: auto; /* Dikey scroll */
            padding: 10px 15px;
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
        //'pagination'  => $pagination,  // Veriyi gönderiyoruz

        'isPicker' => true,
    ]);
    // deneme

    Modal::end();
