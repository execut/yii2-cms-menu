<?php

use yii\db\Schema;
use yii\db\Migration;

class m150922_114805_change_entity_id_field extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('menu_item', 'entity_id');
        $this->addColumn('{{%menu_item}}', 'entity_id', $this->string(50)->notNull());
    }

    public function safeDown()
    {
        echo "m150922_114805_change_entity_id_field cannot be reverted.\n";

        return false;
    }
}
