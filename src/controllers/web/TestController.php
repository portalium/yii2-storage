<?php

namespace portalium\storage\controllers\web;

use portalium\web\Controller as WebController;
use portalium\storage\models\Storage;

class TestController extends WebController
{
    public function actionIndex()
    {
        $model = new Storage();

        return $this->render('index', [
            'model' => $model,
        ]);
    }

}
