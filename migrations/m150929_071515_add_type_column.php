<?php

use yii\db\Schema;
use yii\db\Migration;

class m150929_071515_add_type_column extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%menu_item}}', 'type', "pages_type NOT NULL DEFAULT 'user-defined'");
    }

    public function safeDown()
    {
        $this->dropColumn('{{%menu_item}}', 'type');
    }
}
