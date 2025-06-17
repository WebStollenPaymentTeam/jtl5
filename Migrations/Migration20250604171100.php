<?php

namespace Plugin\ws5_mollie\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20250604171100  extends Migration implements IMigration
{

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('ALTER TABLE xplugin_ws5_mollie_orders MODIFY COLUMN cOrderId VARCHAR(64), MODIFY COLUMN cTransactionId VARCHAR(64);');
    }

    public function down()
    {
        // No need to change since 'xplugin_ws5_mollie_orders' is removed in Migration where it is created, and we don't support downgrading of Plugins
    }
}