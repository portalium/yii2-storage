<?php

namespace portalium\storage\controllers\api;
use Yii;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\rest\ActiveController as RestActiveController;

class DefaultController extends RestActiveController
{
    public $modelClass = 'portalium\storage\models\Storage';

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['dataFilter'] = [
            'class' => \yii\data\ActiveDataFilter::class,
            'searchModel' => StorageSearch::class,
        ];
        
        return $actions;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        switch ($action->id) {
            case 'view':
                if (!Yii::$app->user->can('storageApiDefaultView')) 
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this storage.'));
                break;
            case 'create':
                if (!Yii::$app->user->can('storageApiDefaultCreate')) 
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to create this storage.'));
                break;
            case 'update':
                if (!Yii::$app->user->can('storageApiDefaultUpdate')) 
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to update this storage.'));
                break;
            case 'delete':
                if (!Yii::$app->user->can('storageApiDefaultDelete'))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to delete this storage.'));
                break;
            default:
                if (!Yii::$app->user->can('storageApiDefaultIndex') && !Yii::$app->user->can('storageApiDefaultIndexOwn'))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this storage.'));
                break;
        }
        
        return true;
    }

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