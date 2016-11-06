<?php

use yii\db\Schema;
use yii\db\Migration;

class m150806_071513_add_position_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%menu}}', 'position', $this->integer()->unsigned()->notNull());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%menu}}', 'position');
    }
}
