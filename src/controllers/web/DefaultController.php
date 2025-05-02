<?php

namespace portalium\storage\controllers\web;

use portalium\storage\models\StorageDirectory;
use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use portalium\data\ActiveDataProvider;


class DefaultController extends Controller
{
    public function actionIndex()
    {
        $model = new Storage();
        $searchModel = new StorageSearch();

        $fileDataProvider = $searchModel->search($this->request->queryParams);
        $fileDataProvider->pagination->pageParam = 'filePage';

        $directoryDataProvider = new \yii\data\ActiveDataProvider([
            'query' => StorageDirectory::find()->orderBy(['id_directory' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 12,
            ]
        ]);
        $directoryDataProvider->pagination->pageParam = 'folderPage';
        $id_directory = $this->request->get('id_directory');
        if ($id_directory) {
            $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);
        }

        return $this->render('index', [
            'model' => $model,
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $this->request->get('isPicker', false),
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
                if (!$success)
                    Yii::$app->session->setFlash('error', Module::t('File could not be loaded!'));
                else
                    Yii::$app->session->setFlash('success', Module::t('File uploaded successfully!'));
            }
        }
        return $this->renderAjax('_upload-file', [
            'model' => $model,
        ]);
    }

    public function actionNewFolder()
    {
        $model = new StorageDirectory();

        if (Yii::$app->request->isPost) {
            $activeDirectory = Yii::$app->session->get('active_directory', null);
            $model->id_parent = $activeDirectory;

            if ($model->load(Yii::$app->request->post())) {
                $baseName = trim($model->name) !== '' ? $model->name : Module::t('New Folder');
                $name = $baseName;
                $counter = 1;

                while (StorageDirectory::find()
                    ->where(['id_parent' => $model->id_parent, 'name' => $name])
                    ->exists()) {
                    $name = $baseName . ' (' . $counter . ')';
                    $counter++;
                }

                $model->name = $name;

                if ($model->save()) {
                    Yii::$app->session->setFlash('success', Module::t('Folder created successfully!'));
                } else {
                    Yii::$app->session->setFlash('error', Module::t('Failed to create folder!'));
                }

            } else {
                Yii::$app->session->setFlash('error', Module::t('Failed to create folder!'));
            }
        }

        return $this->renderAjax('_new-folder', [
            'model' => $model
        ]);
    }

    public function actionSetActiveDirectory()
    {
        $id = Yii::$app->request->post('id');
        Yii::$app->session->set('active_directory', $id);
    }

    public function actionRenameFolder($id)
    {
        $model = StorageDirectory::findOne($id);
        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
            return '';
        }

        if (Yii::$app->request->isPost) {
            $oldName = $model->name;
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($oldName !== $model->name) {
                    if ($model->save())
                        Yii::$app->session->setFlash('success', Module::t('Folder renamed successfully!'));
                    else
                        Yii::$app->session->setFlash('error', Module::t('Folder name could not be changed in the database!'));
                }
                else
                    Yii::$app->session->setFlash('error', Module::t('No changes were made to the folder name!'));
            }
            else
                Yii::$app->session->setFlash('error', Module::t('Folder name could not be changed!'));
        }

        return $this->renderAjax('_rename-folder', ['model' => $model]);
    }
    public function actionDeleteFolder()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $folder = StorageDirectory::findOne($id);

        if (!$folder)
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
        $this->deleteFolderRecursive($folder);

        Yii::$app->session->setFlash('success', Module::t('Folder and its contents deleted successfully!'));
    }

    protected function deleteFolderRecursive($folder)
    {
        $subFolders = StorageDirectory::findAll(['id_parent' => $folder->id_directory]);
        foreach ($subFolders as $subFolder) {
            $this->deleteFolderRecursive($subFolder);
        }

        $files = Storage::findAll(['id_directory' => $folder->id_directory]);
        foreach ($files as $file) {
            $filePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;
            if (file_exists($filePath))
                @unlink($filePath);
            $file->delete();
        }
        $folder->delete();
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
            }
            return Yii::$app->response->sendFile($path, $file->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        }
        Yii::$app->session->setFlash('error', Module::t('File not found!'));
    }

    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $model->name;

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save())
                Yii::$app->session->setFlash('success', Module::t('File renamed successfully!'));
            else
                Yii::$app->session->setFlash('error', Module::t('File name could not be changed!'));
        }

        return $this->renderAjax('_rename', ['model' => $model]);
    }

    public function actionUpdateFile($id)
    {
        $model = Storage::findOne($id);
        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $oldFilePath = $storagePath . '/' . $model->name;

        if (!file_exists($oldFilePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        if (Yii::$app->request->isPost) {
            $oldFileName = $model->name;

            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file && in_array($model->file->extension, Storage::$allowExtensions)) {
                $path = realpath(Yii::getAlias('@app') . '/../data');
                $filename = md5(rand()) . "." . $model->file->extension;
                $hash = md5_file($model->file->tempName);

                if ($model->file->saveAs($path . '/' . $filename)) {
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }

                    $model->name = $filename;
                    $model->hash_file = $hash;
                    $model->mime_type = Storage::MIME_TYPE[$model->getMIMEType($path . '/' . $filename)];
                    $model->date_update = date('Y-m-d H:i:s');

                    if ($model->save(false))
                        Yii::$app->session->setFlash('success', Module::t('File updated successfully!'));
                    else
                        Yii::$app->session->setFlash('error', Module::t('File update failed!'));
                } else {
                    Yii::$app->session->setFlash('error', Module::t('File could not be saved!'));
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('Invalid file format!'));
            }
        }

        return $this->renderAjax('_update', ['model' => $model]);
    }

    public function actionShareFile($id)
    {
        $model = Storage::findOne($id);
        return $this->renderAjax('_share', [
            'model' => $model,
        ]);
    }

    public function actionCopyFile()
    {
        $id = Yii::$app->request->post('id');
        $sourceModel = Storage::findOne($id);
        if (!$sourceModel) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $sourceModel->name;

        if (!$sourceModel)
            Yii::$app->session->setFlash('error', Module::t('File not found!'));

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $sourceModel->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return  '';
        }
        $newModel = $sourceModel->copyFile();

        if ($newModel)
            Yii::$app->session->setFlash('success', Module::t('File copied successfully!'));
        else
            Yii::$app->session->setFlash('error', Module::t('File could not be copied!'));
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
                }
                if ($file->deleteFile())
                    Yii::$app->session->setFlash('success', Module::t('File deleted successfully!'));
                else
                    Yii::$app->session->setFlash('error', Module::t('File not found!'));
            } else
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
        }
    }
    public function actionPickerModal()
    {
        $query = Storage::find();

        $extensions = Yii::$app->request->get('fileExtensions', []);
        if (!empty($extensions) && is_array($extensions)) {
            $orConditions = ['or'];
            foreach ($extensions as $extension) {
                $orConditions[] = ['like', 'name', $extension];
            }
            $query->andWhere($orConditions);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
            'sort' => [
                'defaultOrder' => ['id_storage' => SORT_DESC],
            ],
        ]);

        return $this->renderAjax('@portalium/storage/widgets/views/_picker-modal', [
            'dataProvider' => $dataProvider
        ]);
    }
}
