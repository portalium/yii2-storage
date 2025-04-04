<?php

namespace portalium\storage\controllers\web;

use portalium\web\Controller as WebController;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;


class TestController extends WebController
{
    public function actionIndex()
    {
        $model = new Storage();
        $searchModel = new StorageSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
}
