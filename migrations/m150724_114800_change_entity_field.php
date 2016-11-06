<?php

use yii\db\Schema;
use yii\db\Migration;

class m150724_114800_change_entity_field extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{%menu_item}}', 'entity');
        $this->addColumn('{{%menu_item}}', 'entity', $this->string()->notNull()->defaultValue('page'));
    }

    public function safeDown()
    {
        echo "m150724_114800_change_entity_field cannot be reverted.\n";

        return false;
    }
}
