<?php

declare(strict_types=1);

namespace SG\Donations;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_sg_donation', function (Create $table): void {
            $table->addColumn('donation_id', 'int')->unsigned()->autoIncrement();
            $table->addColumn('user_id', 'int')->unsigned()->setDefault(0);
            $table->addColumn('username', 'varchar', 50)->setDefault('');
            $table->addColumn('amount', 'decimal', '10,2')->setDefault(0.00);
            $table->addColumn('currency', 'varchar', 10)->setDefault('USD');
            $table->addColumn('message', 'text')->nullable();
            $table->addColumn('donation_date', 'int')->unsigned()->setDefault(0);
            $table->addColumn('visible', 'tinyint', 3)->unsigned()->setDefault(1);
            $table->addPrimaryKey('donation_id');
        });
    }

    public function uninstallStep1(): void
    {
        $this->schemaManager()->dropTable('xf_sg_donation');
    }
}
