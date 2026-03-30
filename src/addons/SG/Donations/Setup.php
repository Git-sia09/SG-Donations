<?php

namespace SG\Donations;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    // -------------------------------------------------------------------------
    // Install steps
    // -------------------------------------------------------------------------

    public function installStep1(): void
    {
        $this->createTable('xf_sg_donations_campaign', function (Create $table) {
            $table->addColumn('campaign_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('description', 'mediumtext')->nullable();
            $table->addColumn('currency_label', 'varchar', 10)->setDefault('USD');
            $table->addColumn('goal_amount', 'decimal', '10,2')->setDefault(0);
            $table->addColumn('start_date', 'int')->setDefault(0);
            $table->addColumn('end_date', 'int')->setDefault(0);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('created_at', 'int')->setDefault(0);
            $table->addPrimaryKey('campaign_id');
        });
    }

    public function installStep2(): void
    {
        $this->createTable('xf_sg_donations_tier', function (Create $table) {
            $table->addColumn('tier_id', 'int')->autoIncrement();
            $table->addColumn('campaign_id', 'int');
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('description', 'mediumtext')->nullable();
            $table->addColumn('amount', 'decimal', '10,2');
            $table->addColumn('duration_months', 'int')->setDefault(1);
            $table->addColumn('user_group_ids', 'blob')->nullable();
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addPrimaryKey('tier_id');
            $table->addKey('campaign_id');
        });
    }

    public function installStep3(): void
    {
        $this->createTable('xf_sg_donations_log', function (Create $table) {
            $table->addColumn('donation_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('campaign_id', 'int');
            $table->addColumn('tier_id', 'int');
            $table->addColumn('amount', 'decimal', '10,2');
            $table->addColumn('payment_ref', 'varchar', 100)->nullable();
            $table->addColumn('status', 'enum', ['queued', 'applied', 'voided'])->setDefault('queued');
            $table->addColumn('queued_until', 'int')->setDefault(0);
            $table->addColumn('applied_at', 'int')->setDefault(0);
            $table->addColumn('applied_by', 'int')->setDefault(0);
            $table->addColumn('voided_at', 'int')->setDefault(0);
            $table->addColumn('voided_by', 'int')->setDefault(0);
            $table->addColumn('void_reason', 'varchar', 500)->nullable();
            $table->addColumn('created_at', 'int')->setDefault(0);
            $table->addColumn('created_by', 'int')->setDefault(0);
            $table->addColumn('created_ip', 'varbinary', 16)->nullable();
            $table->addColumn('voided_ip', 'varbinary', 16)->nullable();
            $table->addColumn('applied_ip', 'varbinary', 16)->nullable();
            $table->addPrimaryKey('donation_id');
            $table->addKey(['campaign_id', 'status']);
            $table->addKey(['user_id', 'status']);
            $table->addKey(['status', 'queued_until']);
        });
    }

    public function installStep4(): void
    {
        $this->createTable('xf_sg_donations_membership', function (Create $table) {
            $table->addColumn('membership_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('campaign_id', 'int');
            $table->addColumn('tier_id', 'int');
            $table->addColumn('donation_id', 'int');
            $table->addColumn('start_date', 'int')->setDefault(0);
            $table->addColumn('end_date', 'int')->setDefault(0);
            $table->addColumn('granted_group_ids', 'blob')->nullable();
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addColumn('created_at', 'int')->setDefault(0);
            $table->addPrimaryKey('membership_id');
            $table->addKey(['user_id', 'campaign_id']);
            $table->addKey(['user_id', 'active']);
        });
    }

    public function installStep5(): void
    {
        $this->createTable('xf_sg_donations_campaign_stats', function (Create $table) {
            $table->addColumn('campaign_id', 'int');
            $table->addColumn('raised_total', 'decimal', '14,2')->setDefault(0);
            $table->addColumn('latest_donors_json', 'mediumtext')->nullable();
            $table->addColumn('last_rebuild_at', 'int')->setDefault(0);
            $table->addPrimaryKey('campaign_id');
        });
    }

    public function installStep6(): void
    {
        $this->createTable('xf_sg_donations_audit_log', function (Create $table) {
            $table->addColumn('audit_id', 'int')->autoIncrement();
            $table->addColumn('content_type', 'varchar', 50);
            $table->addColumn('content_id', 'int')->setDefault(0);
            $table->addColumn('action', 'varchar', 50);
            $table->addColumn('admin_user_id', 'int')->setDefault(0);
            $table->addColumn('ip_address', 'varbinary', 16)->nullable();
            $table->addColumn('event_date', 'int')->setDefault(0);
            $table->addColumn('before_json', 'mediumtext')->nullable();
            $table->addColumn('after_json', 'mediumtext')->nullable();
            $table->addPrimaryKey('audit_id');
            $table->addKey(['content_type', 'content_id']);
            $table->addKey('event_date');
        });
    }

    public function installStep7(): void
    {
        $this->createTable('xf_sg_donations_widget_settings', function (Create $table) {
            $table->addColumn('setting_id', 'int')->autoIncrement();
            $table->addColumn('campaign_ids', 'blob')->nullable();
            $table->addColumn('show_ticker', 'tinyint')->setDefault(1);
            $table->addColumn('show_progress_bar', 'tinyint')->setDefault(1);
            $table->addColumn('show_goal', 'tinyint')->setDefault(1);
            $table->addColumn('hide_ended_campaign_tabs', 'tinyint')->setDefault(0);
            $table->addColumn('ticker_speed', 'int')->setDefault(30);
            $table->addColumn('updated_at', 'int')->setDefault(0);
            $table->addPrimaryKey('setting_id');
        });

        // Insert default single-row settings
        $this->db()->insert('xf_sg_donations_widget_settings', [
            'campaign_ids'              => serialize([]),
            'show_ticker'               => 1,
            'show_progress_bar'         => 1,
            'show_goal'                 => 1,
            'hide_ended_campaign_tabs'  => 0,
            'ticker_speed'              => 30,
            'updated_at'                => \XF::$time,
        ]);
    }

    // -------------------------------------------------------------------------
    // Uninstall
    // -------------------------------------------------------------------------

    public function uninstallStep1(): void
    {
        $this->dropTable('xf_sg_donations_widget_settings');
        $this->dropTable('xf_sg_donations_audit_log');
        $this->dropTable('xf_sg_donations_campaign_stats');
        $this->dropTable('xf_sg_donations_membership');
        $this->dropTable('xf_sg_donations_log');
        $this->dropTable('xf_sg_donations_tier');
        $this->dropTable('xf_sg_donations_campaign');
    }

    // -------------------------------------------------------------------------
    // Upgrade steps (future versions)
    // -------------------------------------------------------------------------
}
