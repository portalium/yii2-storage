<?php

use portalium\db\Migration;
use portalium\storage\Module;
use portalium\workspace\Module as WorkspaceModule;

class m250122_000000_add_id_workspace_to_storage_directory extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        // Add id_workspace column to storage_directory table
        $this->addColumn(
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_workspace',
            $this->integer(11)->null()->after('id_user')
        );

        // Add foreign key for id_workspace
        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_directory-id_workspace}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_workspace',
            '{{%' . WorkspaceModule::$tablePrefix . 'workspace}}',
            'id_workspace',
            'SET NULL'
        );

        // Add index for id_workspace
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage_directory-id_workspace}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_workspace'
        );
    }

    public function safeDown()
    {
        // Drop foreign key
        $this->dropForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage_directory-id_workspace}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}'
        );

        // Drop index
        $this->dropIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage_directory-id_workspace}}',
            '{{%' . Module::$tablePrefix . 'storage_directory}}'
        );

        // Drop column
        $this->dropColumn(
            '{{%' . Module::$tablePrefix . 'storage_directory}}',
            'id_workspace'
        );
    }
}
