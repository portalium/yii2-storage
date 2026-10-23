<?php

use portalium\db\Migration;
use portalium\storage\Module;

class m260915_000000_add_disk_to_storage extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = '{{%' . Module::$tablePrefix . 'storage}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema !== null && $schema->getColumn('disk') !== null) {
            return true;
        }

        $this->addColumn(
            $table,
            'disk',
            $this->string(32)->notNull()->defaultValue('local')->after('name')
        );

        $this->update($table, ['disk' => 'local']);

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-disk}}',
            $table,
            'disk'
        );
    }

    public function safeDown()
    {
        $table = '{{%' . Module::$tablePrefix . 'storage}}';

        $this->dropIndex('{{%idx-' . Module::$tablePrefix . 'storage-disk}}', $table);
        $this->dropColumn($table, 'disk');
    }
}
