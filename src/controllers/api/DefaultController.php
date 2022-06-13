<?php

namespace diginova\storage\controllers\api;
use diginova\storage\models\Storage;
use Yii;
use portalium\rest\ActiveController as RestActiveController;

class DefaultController extends RestActiveController
{
    public $modelClass = 'diginova\storage\models\Storage';

    public function actionUpload()
    {
        $model = new \yii\base\DynamicModel([
            'file' => '',
            'title' => '',
        ]);
        $model->addRule('file', 'required');
        $model->addRule('title', 'string');
        $model->title = Yii::$app->request->post('title');
        $model->file = \yii\web\UploadedFile::getInstanceByName('file');
        if ($model->file && $model->validate()) {
            $path = realpath(Yii::$app->basePath . '/../data');
            $filename = md5(rand()) . "." . $model->file->extension;
            
           
            if ($model->file->saveAs($path . '/' . $filename)) {
                $storage = new Storage();
                $storage->name = $filename;
                $storage->title = $model->title;
                $storage->save();
                return $storage;
            }
        }
        
        return ['status' => 'FAIL'];
    }
}