<?php

namespace portalium\storage\controllers\web;

use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use Yii;
use yii\web\NotFoundHttpException;
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
            $success = ($model->file && $model->upload());

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                if (!$success) {
                    Yii::$app->session->setFlash('error', Module::t('File could not be loaded!'));
                    return ['success' => false,];
                } else {
                    Yii::$app->session->setFlash('success', Module::t('File uploaded successfully!'));
                    return ['success' => true];
                }
            }
        }
        return $this->renderAjax('_upload-file', [
            'model' => $model,
        ]);
    }

    public function actionDownloadFile()
    {
        $id = Yii::$app->request->post('id');
        $file = Storage::findOne($id);

        if ($file) {
            $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

            if (!file_exists($path)) {
                Storage::deleteAll(['id_storage' => $file->id_storage]);
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
                return ['success' => false];
            }

            return Yii::$app->response->sendFile($path, $file->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        }

        Yii::$app->session->setFlash('error', Module::t('File not found!'));
        return ['success' => false];
    }



    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);

        if (!$model) {
            return $this->asJson(['success' => false]);
        }

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $model->name;

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return $this->asJson(['success' => false]);
        }

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', Module::t('File renamed successfully!'));
                return $this->asJson(['success' => true]);
            } else {
                Yii::$app->session->setFlash('error', Module::t('File name could not be changed!'));
                return $this->asJson(['success' => false]);
            }
        }
        return $this->renderAjax('_rename', ['model' => $model]);
    }

    public function actionUpdateFile($id)
    {
        $model = Storage::findOne($id);

        if (!$model) {
            return $this->asJson(['success' => false]);
        }
        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $model->name;

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return $this->asJson(['success' => false]);
        }
        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file && in_array($model->file->extension, Storage::$allowExtensions)) {
                $path = realpath(Yii::$app->basePath . '/../data');
                $filename = md5(rand()) . "." . $model->file->extension;
                $hash = md5_file($model->file->tempName);

                if ($model->file->saveAs($path . '/' . $filename)) {
                    $model->name = $filename;
                    $model->hash_file = $hash;
                    $model->mime_type = Storage::MIME_TYPE[$model->getMIMEType($path . '/' . $filename)];
                    $model->date_update = date('Y-m-d H:i:s');

                    if ($model->save(false)) {
                        Yii::$app->session->setFlash('success', Module::t('File updated successfully!'));
                        return $this->asJson(['success' => true]);
                    }
                }
            }
            Yii::$app->session->setFlash('error', Module::t('File update failed!'));
            return $this->asJson(['success' => false]);
        }

        return $this->renderAjax('_update', ['model' => $model]);
    }
    public function actionCopyFile()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $sourceModel = Storage::findOne($id);
        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $sourceModel->name;

        if (!$sourceModel) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return ['success' => false];
        }

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $sourceModel->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return $this->asJson(['success' => false]);
        }
        $newModel = $sourceModel->copyFile();

        if ($newModel) {
            Yii::$app->session->setFlash('success', Module::t('File copied successfully!'));
            return ['success' => true];
        } else {
            Yii::$app->session->setFlash('error', Module::t('File could not be copied!'));
            return ['success' => false];
        }
    }

    public function actionDeleteFile()
    {
        $fileId = Yii::$app->request->post('id');

        if (Yii::$app->request->isPost && Yii::$app->request->validateCsrfToken()) {
            $file = Storage::findOne($fileId);

            if ($file) {
                $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

                if (!file_exists($path)) {
                    Storage::deleteAll(['id_storage' => $file->id_storage]);
                    Yii::$app->session->setFlash('error', Module::t('File not found!'));
                    return ['success' => false];
                }

                if ($file->deleteFile()) {
                    Yii::$app->session->setFlash('success', Module::t('File deleted successfully!'));
                    return ['success' => true];
                } else {
                    Yii::$app->session->setFlash('error', Module::t('File could not be deleted!'));
                    return ['success' => false];
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
                return ['success' => false];
            }
        }
    }

}
