<?php

use portalium\storage\Module;
use portalium\db\Migration;

class m250121_120000_storage_share_system extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Create storage_storage_share table
        $this->createTable('{{%' . Module::$tablePrefix . 'storage_share}}', [
            'id_share' => $this->primaryKey(),
            'id_storage' => $this->integer()->null()->comment('File share (NULL for directory/full storage share)'),
            'id_directory' => $this->integer()->null()->comment('Directory share (NULL for file/full storage share)'),
            'id_user_owner' => $this->integer()->null()->comment('Owner user (NULL for file/directory share, Set for full storage share)'),
            'shared_with_type' => $this->string(20)->notNull()->comment('user, workspace, link'),
            'id_shared_with' => $this->integer()->null()->comment('User/Workspace ID (NULL for link type)'),
            'permission_level' => $this->string(20)->notNull()->defaultValue('view')->comment('view, edit, manage'),
            'is_active' => $this->boolean()->notNull()->defaultValue(1),
            'share_token' => $this->string(64)->null()->unique()->comment('Unique token for link-based sharing'),
            'expires_at' => $this->timestamp()->null()->comment('Expiration date for share'),
            'date_create' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'date_update' => $this->timestamp()->null()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Add foreign keys
        $this->addForeignKey(
            'fk_storage_share_storage',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_storage',
            '{{%' . Module::$tablePrefix . 'storage}}',
            'id_storage',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_storage_share_directory',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_directory',
            '{{%storage_storage_directory}}',
            'id_directory',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_storage_share_owner',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_user_owner',
            '{{%user_user}}',
            'id_user',
            'CASCADE',
            'CASCADE'
        );

        // Add indexes for better query performance
        $this->createIndex(
            'idx_storage_share_storage',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_storage'
        );

        $this->createIndex(
            'idx_storage_share_directory',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_directory'
        );

        $this->createIndex(
            'idx_storage_share_owner',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'id_user_owner'
        );

        $this->createIndex(
            'idx_storage_share_type',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            ['shared_with_type', 'id_shared_with']
        );

        $this->createIndex(
            'idx_storage_share_token',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'share_token'
        );

        $this->createIndex(
            'idx_storage_share_active',
            '{{%' . Module::$tablePrefix . 'storage_share}}',
            'is_active'
        );

        // Add constraint to ensure only one of (id_storage, id_directory, id_user_owner) is set
        // This will be handled at application level
    }

    public function down()
    {
        $this->dropTable('{{%' . Module::$tablePrefix . 'storage_share}}');
    }
}
