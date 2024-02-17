<?php

namespace portalium\storage\controllers\api;

use Yii;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\rest\ActiveController as RestActiveController;
use yii\web\NotFoundHttpException;

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
                if (!Yii::$app->user->can('storageApiDefaultView', ['id_module' => 'storage']))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this storage.'));
                break;
            case 'create':
                if (!Yii::$app->user->can('storageApiDefaultCreate'))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to create this storage.'));
                break;
            case 'update':
                if (!Yii::$app->user->can('storageApiDefaultUpdate', ['id_module' => 'storage']))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to update this storage.'));
                break;
            case 'delete':
                if (!Yii::$app->user->can('storageApiDefaultDelete', ['id_module' => 'storage']))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to delete this storage.'));
                break;
            default:
                if (!Yii::$app->user->can('storageApiDefaultIndex', ['id_module' => 'storage']) && !Yii::$app->user->can('storageApiDefaultIndexOwn', ['id_module' => 'storage']))
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
                $storage->id_workspace = Yii::$app->workspace->id;
                try {
                    $storage->mime_type = Storage::MIME_TYPE[$storage->getMIMEType($path . '/' . $filename)];
                } catch (\Throwable $th) {
                    $storage->mime_type = Storage::MIME_TYPE['video/mpeg'];
                }

                $storage->save();
                return $storage;
            }
        }

        return ['status' => 'FAIL'];
    }

    public function actionGetFile($id)
    {
        $files = Storage::findForApi()->andWhere(['id_storage' => $id])->all();
        if (empty($files)) {
            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
        }
        /*  if (!Yii::$app->user->can('storageWebDefaultGetFile', ['model' => $file])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        } */
        
        $path = Yii::$app->basePath . '/../'. Yii::$app->setting->getValue('storage::path') . '/' . $files[0]->name;
        
        if (file_exists($path)) {
            return Yii::$app->response->sendFile($path, $files[0]->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        } else {
            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
        }
    }
}