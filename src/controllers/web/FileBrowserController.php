<?php

namespace portalium\storage\controllers\web;

use portalium\workspace\models\WorkspaceUser;
use Yii;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use portalium\web\Controller;
use yii\web\NotFoundHttpException;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\storage\Module;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

/**
 * StorageController implements the CRUD actions for Storage model.
 */
class FileBrowserController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function beforeAction($action)
    {
        if (WorkspaceUser::getActiveWorkspaceId() == null) {
            Yii::$app->session->setFlash('error', Module::t('You must select a workspace first.'));
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                throw new ForbiddenHttpException(Module::t('You must select a workspace first.'));
            } else {
                throw new NotFoundHttpException(Module::t('You must select a workspace first.'));
            }
        }
        if ($action->id == 'index') {
            $this->enableCsrfValidation = false;
            Yii::$app->controller->enableCsrfValidation = false;
        }
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    /**
     * Lists all Storage models.
     *
     * @return string
     */
    public function actionIndex()
    {

        if (!\Yii::$app->user->can('storageWebDefaultIndex', ['id_module' => 'storage'])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => Storage::find(),
            'pagination' => false,
            'sort' => [
                'defaultOrder' => [
                    'id_storage' => SORT_DESC,
                ]
            ],
        ]);
        
        $privateQuery =  Storage::find();
        $privateQuery->andWhere(['access' => Storage::ACCESS_PRIVATE]); 
        $privateDataProvider = new \yii\data\ActiveDataProvider([
            'query' => $privateQuery,
            'pagination' => false,
            'sort' => [
                'defaultOrder' => [
                    'id_storage' => SORT_DESC,
                ]
            ],
        ]);



        $publicQuery =  Storage::find();
        $publicQuery->andWhere(['access' => Storage::ACCESS_PUBLIC]);
        $publicDataProvider = new \yii\data\ActiveDataProvider([
            'query' => $publicQuery,
            'pagination' => false,
            'sort' => [
                'defaultOrder' => [
                    'id_storage' => SORT_DESC,
                ]
            ],
        ]);
        if (Yii::$app->request->isAjax || Yii::$app->request->isPjax || Yii::$app->request->get('payload')) {
            $model = new Storage();
            $payload = Yii::$app->request->get('payload');

            $payload = json_decode($payload, true);
            $id_storage = $payload['id_storage'] ?? null;

            if ($id_storage) {
                $model = Storage::findOne($id_storage);
            }
            $query = Storage::find();
            if ($payload['fileExtensions']) {
                foreach ($payload['fileExtensions'] as $fileExtension) {
                    $query->orWhere(['like', 'name', $fileExtension]);
                }
            }
            $dataProvider = new \yii\data\ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
                'sort' => [
                    'defaultOrder' => [
                        'id_storage' => SORT_DESC,
                    ]
                ],
            ]);
            
            return $this->render('index', [
                'attribute' => $payload['attribute'] ?? null,
                'multiple' => $payload['multiple'] ?? null,
                'dataProvider' => $dataProvider,
                'privateDataProvider' => $privateDataProvider,
                'publicDataProvider' => $publicDataProvider,
                'isJson' => $payload['isJson'] ?? null,
                'storageModel' => $model,
                'attributes' => $payload['attributes'] ?? null,
                'name' => $payload['name'] ?? null,
                'callbackName' => $payload['callbackName'] ?? null,
                'isPicker' => $payload['isPicker'] ?? null,
                'fileExtensions' => $payload['fileExtensions'] ?? null,
            ]);
        } else {
            $model = new Storage();
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'privateDataProvider' => $privateDataProvider,
                'publicDataProvider' => $publicDataProvider,
                'isJson' => Yii::$app->request->get('isJson'),
                'isPicker' => false,
                'storageModel' => $model,
                'name' => 'base',
            ]);
        }
    }

    public function actionGetName($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = Storage::findOne($id);
        if (!$model) {
            return "None";
        }
        return $model->name;
    }

    /**
     * Displays a single Storage model.
     * @param int $id Id Storage
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->can('storageWebDefaultView', ['id_module' => 'storage'])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Storage model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('storageWebDefaultCreate', ['id_module' => 'storage'])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Storage();

        if ($this->request->isAjax && $this->request->isPost) {
            if ($file = UploadedFile::getInstanceByName('file')) {
                $model->file = $file;
                $model->title = $this->request->post('title');

                $fileName = md5(rand()) . '.' . $file->extension;
                if ($file->saveAs(Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $fileName)) {
                    $model->name = $fileName;
                    $model->id_user = Yii::$app->user->id;
                    $model->access = $this->request->post('access');
                    $model->mime_type = (Storage::MIME_TYPE[$file->type] ?? Storage::MIME_TYPE['other']);
                    $model->id_workspace = WorkspaceUser::getActiveWorkspaceId();

                    if ($model->save()) {
                        $model = new Storage();
                        \Yii::$app->session->addFlash('success', Module::t('File uploaded successfully'));
                    } else {
                        unlink(Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $fileName);
                        \Yii::$app->session->addFlash('error', Module::t('Error uploading file'));
                        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                        return ['success' => false, 'error' => $model->getErrors()];
                    }
                }
            }
            //response format json
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => true];
        }
        $widgetName = ($this->request->get('name')) ? $this->request->get('name') : '';
        return $this->renderAjax('_formModal', [
            'model' => $model,
            'widgetName' => $widgetName
        ]);
    }

    /**
     * Updates an existing Storage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id Id Storage
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('storageWebDefaultUpdate', ['model' => $this->findModel($id), 'id_module' => 'storage'])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = $this->findModel($id);
        if (!$model) {
            throw new HttpException(404, Module::t('The requested page does not exist.'));
        }
        if ($this->request->isAjax) {
            if ($file = UploadedFile::getInstance($model, 'file[' . $id . ']')) {
                if ($model->load($this->request->post())) {
                    $oldFileName = $model->name;
                    $fileName = md5(rand()) . '.' . $file->extension;
                    if ($file->saveAs(Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $fileName)) {
                        $model->name = $fileName;
                        $model->id_user = Yii::$app->user->id;
                        $model->access = $this->request->post('access');
                        $model->mime_type = (Storage::MIME_TYPE[$file->type] ?? Storage::MIME_TYPE['other']);
                        $model->id_workspace = WorkspaceUser::getActiveWorkspaceId();
                        if ($model->save()) {
                            unlink(Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $oldFileName);
                            $model = new Storage();
                            \Yii::$app->session->addFlash('success', Module::t('File uploaded successfully'));
                        } else {
                            unlink(Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $fileName);
                            \Yii::$app->session->addFlash('error', Module::t('Error uploading file'));
                        }
                    }
                }
            } else {
                $model->title = $this->request->post('title');
                $model->file = UploadedFile::getInstanceByName('file');
                $model->access = $this->request->post('access');
                if ($model->file) {
                    $model->deleteFile($model->name);
                }
                if ($model->upload()) {
                    return json_encode(['name' => $model->name]);
                } else {
                    return json_encode(['error' => Module::t('Error uploading file')]);
                }
            }

            //response format json
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => true];
        }

        return $this->renderAjax('update', [
            'model' => $model,
        ]);
    }

    protected function updatePjax($id)
    {
        $model = $this->findModel($id);
        if (!Yii::$app->user->can('storageWebDefaultUpdate', ['model' => $this->findModel($id)])) {
            return json_encode(['error' => Module::t('Error uploading file')]);
        }
        $model->title = $this->request->post('title');
        $model->file = UploadedFile::getInstanceByName('file');
        if ($model->file) {
            $model->deleteFile($model->name);
        }
        if ($model->upload()) {
            return json_encode(['name' => $model->name]);
        } else {
            return json_encode(['error' => Module::t('Error uploading file')]);
        }
    }

    /**
     * Deletes an existing Storage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id Id Storage
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->isPost ? $this->findModel(Yii::$app->request->post('id')) : $this->findModel(Yii::$app->request->get('id'));

        if (!Yii::$app->user->can('storageWebDefaultDelete', ['model' => $this->findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = $this->findModel($id);
        if (!$model->deleteFile($model->name)) {
            \Yii::$app->session->addFlash('error', Module::t('Error deleting file'));
        }

        if (!$model->delete()) {
            \Yii::$app->session->addFlash('error', Module::t('Error deleting file'));
        }
        if ($this->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => true];
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
