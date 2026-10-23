<?php

use portalium\storage\Module;
use yii\db\Migration;

class m261014_000014_storage_access_default_private extends Migration
{
    public function safeUp()
    {
        $table = '{{%' . Module::$tablePrefix . 'storage}}';
        $schema = $this->db->getTableSchema($table, true);
        $column = $schema !== null ? $schema->getColumn('access') : null;
        if ($column === null) {
            return true;
        }
        if ($column->dbType === 'tinyint(4)' && !$column->allowNull && (string) $column->defaultValue === '0') {
            return true;
        }
        $this->update($table, ['access' => 0], ['access' => null]);
        $this->alterColumn($table, 'access', $this->tinyInteger(4)->notNull()->defaultValue(0));
        return true;
    }

    public function safeDown()
    {
        return true;
    }
}
