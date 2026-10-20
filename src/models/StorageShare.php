<?php

namespace portalium\storage\models;

use Yii;
use portalium\storage\Module;
use portalium\user\models\User;
use portalium\workspace\models\Workspace;

/**
 * This is the model class for table "{{%storage_storage_share}}".
 *
 * @property int $id_share
 * @property int|null $id_storage File share
 * @property int|null $id_directory Directory share
 * @property int|null $id_user_owner Owner user for full storage share
 * @property string $shared_with_type user, workspace, link
 * @property int|null $id_shared_with User/Workspace ID
 * @property string $permission_level view, edit, manage
 * @property int $is_active
 * @property string|null $share_token
 * @property string|null $expires_at
 * @property string $date_create
 * @property string|null $date_update
 *
 * @property Storage $storage
 * @property Storage $directory
 * @property User $owner
 * @property User $sharedWithUser
 * @property Workspace $sharedWithWorkspace
 */
class StorageShare extends \yii\db\ActiveRecord
{
    const TYPE_USER = 'user';
    const TYPE_WORKSPACE = 'workspace';
    const TYPE_LINK = 'link';

    const PERMISSION_VIEW = 'view';
    const PERMISSION_EDIT = 'edit';
    const PERMISSION_MANAGE = 'manage';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%' . Module::$tablePrefix . 'storage_share}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_storage', 'id_directory', 'id_user_owner', 'id_shared_with', 'is_active'], 'integer'],
            [['shared_with_type', 'permission_level'], 'required'],
            [['expires_at', 'date_create', 'date_update'], 'safe'],
            [['shared_with_type', 'permission_level'], 'string', 'max' => 20],
            [['share_token'], 'string', 'max' => 64],
            [['share_token'], 'unique'],
            [['is_active'], 'default', 'value' => 1],
            [['permission_level'], 'default', 'value' => self::PERMISSION_VIEW],
            
            // Validation rules
            ['shared_with_type', 'in', 'range' => [self::TYPE_USER, self::TYPE_WORKSPACE, self::TYPE_LINK]],
            ['permission_level', 'in', 'range' => [self::PERMISSION_VIEW, self::PERMISSION_EDIT, self::PERMISSION_MANAGE]],
            
            // Custom validation: Only one of id_storage, id_directory, id_user_owner should be set
            ['id_storage', 'validateShareType'],
            
            // If type is user or workspace, id_shared_with is required
            ['id_shared_with', 'required', 'when' => function($model) {
                return in_array($model->shared_with_type, [self::TYPE_USER, self::TYPE_WORKSPACE]);
            }],
            
            // Foreign key validations
            [['id_storage'], 'exist', 'skipOnError' => true, 'targetClass' => Storage::class, 'targetAttribute' => ['id_storage' => 'id_storage']],
            [['id_directory'], 'exist', 'skipOnError' => true, 'targetClass' => Storage::class, 'targetAttribute' => ['id_directory' => 'id_storage']],
            [['id_user_owner'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user_owner' => 'id_user']],
            [['id_shared_with'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_shared_with' => 'id_user'], 'when' => function($model) {
                return $model->shared_with_type === self::TYPE_USER;
            }],
            [['id_shared_with'], 'exist', 'skipOnError' => true, 'targetClass' => Workspace::class, 'targetAttribute' => ['id_shared_with' => 'id_workspace'], 'when' => function($model) {
                return $model->shared_with_type === self::TYPE_WORKSPACE;
            }],
        ];
    }

    /**
     * Custom validator: Only one of id_storage, id_directory, id_user_owner should be set
     */
    public function validateShareType($attribute, $params)
    {
        $count = 0;
        if ($this->id_storage !== null) $count++;
        if ($this->id_directory !== null) $count++;
        if ($this->id_user_owner !== null) $count++;
        
        if ($count !== 1) {
            $this->addError($attribute, Module::t('Only one of id_storage, id_directory, or id_user_owner must be set.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_share' => Module::t('ID'),
            'id_storage' => Module::t('Storage'),
            'id_directory' => Module::t('Directory'),
            'id_user_owner' => Module::t('Owner'),
            'shared_with_type' => Module::t('Share Type'),
            'id_shared_with' => Module::t('Shared With'),
            'permission_level' => Module::t('Permission Level'),
            'is_active' => Module::t('Active'),
            'share_token' => Module::t('Share Token'),
            'expires_at' => Module::t('Expires At'),
            'date_create' => Module::t('Created'),
            'date_update' => Module::t('Updated'),
        ];
    }

    /**
     * Gets query for [[Storage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStorage()
    {
        return $this->hasOne(Storage::class, ['id_storage' => 'id_storage']);
    }

    /**
     * Gets query for [[Directory]].
     * Now points to Storage model since directories are stored in the same table.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDirectory()
    {
        return $this->hasOne(Storage::class, ['id_storage' => 'id_directory']);
    }

    /**
     * Gets query for [[Owner]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne(User::class, ['id_user' => 'id_user_owner']);
    }

    /**
     * Gets query for [[SharedWithUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSharedWithUser()
    {
        return $this->hasOne(User::class, ['id_user' => 'id_shared_with']);
    }

    /**
     * Gets query for [[SharedWithWorkspace]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSharedWithWorkspace()
    {
        return $this->hasOne(Workspace::class, ['id_workspace' => 'id_shared_with']);
    }

    /**
     * Generate unique share token
     */
    public function generateShareToken()
    {
        $this->share_token = Yii::$app->security->generateRandomString(32);
    }

    /**
     * Check if share is expired
     * 
     * @return bool
     */
    public function isExpired()
    {
        if ($this->expires_at === null) {
            return false;
        }
        
        return strtotime($this->expires_at) < time();
    }

    /**
     * Check if share is valid (active and not expired)
     * 
     * @return bool
     */
    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get share type label
     * 
     * @return string
     */
    public function getShareTypeLabel()
    {
        switch ($this->shared_with_type) {
            case self::TYPE_USER:
                return Module::t('User');
            case self::TYPE_WORKSPACE:
                return Module::t('Workspace');
            case self::TYPE_LINK:
                return Module::t('Link');
            default:
                return $this->shared_with_type;
        }
    }

    /**
     * Get permission level label
     * 
     * @return string
     */
    public function getPermissionLabel()
    {
        switch ($this->permission_level) {
            case self::PERMISSION_VIEW:
                return Module::t('View');
            case self::PERMISSION_EDIT:
                return Module::t('Edit');
            case self::PERMISSION_MANAGE:
                return Module::t('Manage');
            default:
                return $this->permission_level;
        }
    }

    /**
     * Get shared with name
     * 
     * @return string
     */
    public function getSharedWithName()
    {
        if ($this->shared_with_type === self::TYPE_USER && $this->sharedWithUser) {
            return $this->sharedWithUser->username;
        } elseif ($this->shared_with_type === self::TYPE_WORKSPACE && $this->sharedWithWorkspace) {
            return $this->sharedWithWorkspace->name;
        } elseif ($this->shared_with_type === self::TYPE_LINK) {
            return Module::t('Anyone with the link');
        }
        
        return Module::t('Unknown');
    }

    /**
     * Check if a user has access to a storage item through shares
     * 
     * @param int $id_user User ID to check
     * @param Storage|null $storage Storage model (file)
     * @param Storage|null $directory Directory model (Storage with type=directory)
     * @param string $requiredPermission Required permission level
     * @return bool
     */
    public static function hasAccess($id_user, $storage = null, $directory = null, $requiredPermission = self::PERMISSION_VIEW)
    {
        $query = self::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ]);

        // A share grants access only to the item it covers:
        // file share -> that file; directory share -> that directory and its descendants;
        // full-storage share (both file and directory empty) -> all of the owner's items.
        $item = $storage ?? $directory;
        if ($item !== null) {
            $scope = ['OR',
                ['and', ['id_user_owner' => $item->id_user], ['id_storage' => null], ['id_directory' => null]],
            ];
            if ($storage !== null) {
                $scope[] = ['id_storage' => $storage->id_storage];
            }
            $start = $directory !== null ? $directory->id_storage : $storage->id_directory;
            if ($start) {
                $scope[] = ['and', ['id_storage' => null], ['id_directory' => self::getParentDirectoryIds($start)]];
            }
            $query->andWhere($scope);
        }

        // Check if shared with this user or their workspace
        $userWorkspaceIds = Yii::$app->user->isGuest ? [] : 
            \portalium\workspace\models\WorkspaceUser::find()
                ->select('id_workspace')
                ->where(['id_user' => $id_user])
                ->column();

        $query->andWhere(['OR',
            ['shared_with_type' => self::TYPE_USER, 'id_shared_with' => $id_user],
            ['shared_with_type' => self::TYPE_WORKSPACE, 'id_shared_with' => $userWorkspaceIds],
        ]);

        // Check permission level
        $permissionHierarchy = [
            self::PERMISSION_VIEW => 1,
            self::PERMISSION_EDIT => 2,
            self::PERMISSION_MANAGE => 3,
        ];

        $requiredLevel = $permissionHierarchy[$requiredPermission] ?? 1;
        $validPermissions = array_keys(array_filter($permissionHierarchy, function($level) use ($requiredLevel) {
            return $level >= $requiredLevel;
        }));

        $query->andWhere(['permission_level' => $validPermissions]);

        return $query->exists();
    }

    const PERMISSION_LEVELS = [
        self::PERMISSION_VIEW => 1,
        self::PERMISSION_EDIT => 2,
        self::PERMISSION_MANAGE => 3,
    ];

    /**
     * Numeric value of a share's permission level; an unknown level is 0.
     */
    public static function levelOf($permission)
    {
        return self::PERMISSION_LEVELS[$permission] ?? 0;
    }

    /**
     * Whether this share allows the requested level (view < edit < manage).
     */
    public function allows($requiredPermission)
    {
        $required = self::levelOf($requiredPermission);
        return $required > 0 && self::levelOf($this->permission_level) >= $required;
    }

    /**
     * Whether this share covers the given item.
     */
    public function covers($item)
    {
        if ($item === null) {
            return false;
        }
        if ($this->id_storage) {
            return (int) $this->id_storage === (int) $item->id_storage;
        }
        if ($this->id_directory) {
            $start = $item->type === Storage::TYPE_DIRECTORY ? $item->id_storage : $item->id_directory;
            if (!$start) {
                return false;
            }
            return in_array((int) $this->id_directory, array_map('intval', self::getParentDirectoryIds($start)), true);
        }
        if ($this->id_user_owner) {
            return (int) $this->id_user_owner === (int) $item->id_user;
        }
        return false;
    }

    /**
     * Whether this share was granted to the current session's user. A link share only
     * counts once its token has been opened in this session (actionViewShare); knowing
     * the id alone is not enough.
     */
    public function isForCurrentUser()
    {
        switch ($this->shared_with_type) {
            case self::TYPE_USER:
                return !Yii::$app->user->isGuest && (int) $this->id_shared_with === (int) Yii::$app->user->id;
            case self::TYPE_WORKSPACE:
                return !Yii::$app->user->isGuest
                    && Yii::$app->workspace->isMember($this->id_shared_with, Yii::$app->user->id);
            case self::TYPE_LINK:
                $opened = Yii::$app->has('session') ? (array) Yii::$app->session->get('storage_opened_link_shares', []) : [];
                return in_array((int) $this->id_share, array_map('intval', $opened), true);
        }
        return false;
    }

    /**
     * Whether a share referenced by id_share authorizes this operation on this item.
     */
    public function grants($item, $requiredPermission)
    {
        return $this->isValid() && $this->allows($requiredPermission) && $this->covers($item) && $this->isForCurrentUser();
    }

    /**
     * Records in the session that a link share's token has been opened.
     */
    public function rememberOpenedLink()
    {
        if (Yii::$app->has('session')) {
            $opened = (array) Yii::$app->session->get('storage_opened_link_shares', []);
            $opened[] = (int) $this->id_share;
            Yii::$app->session->set('storage_opened_link_shares', array_values(array_unique($opened)));
        }
    }

    /**
     * Get all parent directory IDs for hierarchical share checking.
     * Now uses Storage model since directories are in the same table.
     * 
     * @param int $id_directory The storage ID of the directory
     * @return array
     */
    public static function getParentDirectoryIds($id_directory)
    {
        $ids = [$id_directory];
        $current = Storage::findOne(['id_storage' => $id_directory, 'type' => Storage::TYPE_DIRECTORY]);
        
        while ($current && $current->id_directory) {
            $ids[] = $current->id_directory;
            $current = Storage::findOne(['id_storage' => $current->id_directory, 'type' => Storage::TYPE_DIRECTORY]);
        }
        
        return $ids;
    }

    /**
     * Get all shares for a storage item
     * 
     * @param Storage|null $storage File model
     * @param Storage|null $directory Directory model (Storage with type=directory)
     * @param int|null $id_user_owner For full storage shares
     * @return \yii\db\ActiveQuery
     */
    public static function getShares($storage = null, $directory = null, $id_user_owner = null)
    {
        $query = self::find()->where(['is_active' => 1]);

        if ($storage !== null) {
            $query->andWhere(['id_storage' => $storage->id_storage]);
        } elseif ($directory !== null) {
            $query->andWhere(['id_directory' => $directory->id_storage]);
        } elseif ($id_user_owner !== null) {
            $query->andWhere(['id_user_owner' => $id_user_owner]);
        }

        return $query;
    }
}
