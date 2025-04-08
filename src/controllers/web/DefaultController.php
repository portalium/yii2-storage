<?php

namespace portalium\storage\controllers\web;

use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use Yii;
use yii\web\UploadedFile;


class DefaultController extends Controller
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

    public function actionUploadFile()
    {
        $model = new Storage();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file && $model->upload()) {
                Yii::$app->session->setFlash('success', Module::t('File uploaded successfully!'));
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', Module::t('An error occurred while uploading the file!'));
                return $this->redirect(['index']);
            }
        }
        return $this->renderAjax('_upload-file', [
            'model' => $model,
        ]);
    }


    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
        if ($model) {
            return $this->renderAjax('_rename', [
                'model' => $model,
            ]);
        }
        return null;
    }
}
