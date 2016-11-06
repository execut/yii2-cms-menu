<?php

use yii\db\Schema;
use yii\db\Migration;

class m150723_143855_add_public_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%menu_item}}', 'public', $this->boolean()->notNull()->defaultValue('true'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%menu_item}}', 'public');
    }
}
