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

    public function actionGetFile($id, $access_token = null)
    {
        $file = Storage::findOne($id);
        $path = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

        if (file_exists($path)) {
            return Yii::$app->response->sendFile($path, $file->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        } else {
            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
        }
    }
    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);

        if (!$model) {
            return $this->asJson(['success' => false]);
        }
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'File renamed successfully!');
                return $this->asJson([
                    'success' => true
                ]);
            } else {
                Yii::$app->session->setFlash('error', ' File name could not be changed!');

                return $this->asJson([
                    'success' => false
                ]);
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

    public function actionCopy($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $file = Storage::findOne($id);
            if (!$file) {
                return $this->jsonResponse(false, 'File not found in the database.');
            }
            $storagePath = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/';
            $originalFilePath = $storagePath . $file->name;
            $newFileName = md5(uniqid()) . '.' . pathinfo($file->name, PATHINFO_EXTENSION);
            $newFilePath = $storagePath . $newFileName;

            if (!file_exists($originalFilePath)) {
                return $this->jsonResponse(false, 'Original file not found: {path}', ['path' => $originalFilePath]);
            }

            if (!copy($originalFilePath, $newFilePath)) {
                return $this->jsonResponse(false, 'Failed to physically copy the file.');
            }
            $originalTitle = $file->title;
            $baseTitle = preg_replace('/_\d+$/', '', $originalTitle);
            $pattern = '/^' . preg_quote($baseTitle, '/') . '_(\d+)$/';
            $maxNumber = 0;
            $similarFiles = Storage::find()
                ->where(['like', 'title', $baseTitle . '_'])
                ->all();
            foreach ($similarFiles as $similarFile) {
                if (preg_match($pattern, $similarFile->title, $matches)) {
                    $number = (int)$matches[1];
                    if ($number > $maxNumber) {
                        $maxNumber = $number;
                    }
                }
            }
            $newTitle = $baseTitle . '_' . ($maxNumber + 1);

            $newFile = new Storage([
                'attributes' => $file->attributes,
                'title' => $newTitle,
                'name' => $newFileName
            ]);

            if ($newFile->save()) {
                return $this->jsonResponse(true, 'File successfully copied.');
            }
            unlink($newFilePath);
            return $this->jsonResponse(false, 'Failed to save the copied file.');
        } catch (\Exception $e) {
            return $this->jsonResponse(false, 'An unexpected error occurred: {error}', ['error' => $e->getMessage()]);
        }
    }
    private function jsonResponse($success, $message, $params = [])
    {
        return ['success' => $success, 'message' => Module::t($message, $params)];
    }

    public function actionDelete()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $request = \Yii::$app->request;
            $id = $request->post('id');

            if (empty($id)) {
                return ['success' => false, 'message' => 'Missing ID parameter'];
            }

            $model = $this->findModel($id);

            if (!$model) {
                return ['success' => false, 'message' => 'Record not found'];
            }

            if (!\Yii::$app->user->can('storageWebDefaultDelete', ['model' => $model, 'id_module' => 'storage'])) {
                return ['success' => false, 'message' => Module::t('Permission denied')];
            }

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if ($model->delete()) {
                    if (!$model->deleteFile($model->name)) {
                        $transaction->rollBack();
                        return ['success' => false, 'message' => Module::t('File deleted from DB, but file could not be deleted from disk.')];
                    }
                    $transaction->commit();
                    return ['success' => true, 'message' => Module::t('File deleted successfully.')];
                } else {
                    $transaction->rollBack();
                    return ['success' => false, 'message' => 'Unable to delete record from database'];
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'System error: ' . $e->getMessage()];
        }
    }

    /**
     * Finds the Storage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id Id Storage
     * @return Storage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Storage::findOne(['id_storage' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }
}
