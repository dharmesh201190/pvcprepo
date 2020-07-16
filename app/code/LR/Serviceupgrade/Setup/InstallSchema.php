<?php
namespace LR\Serviceupgrade\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        if (!$installer->tableExists('lr_serviceupgrade')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('lr_serviceupgrade'))
                ->addColumn(
                    'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Id'
                )
                ->addColumn(
                    'range_min_price', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Min Price'
                )
                ->addColumn(
                    'range_max_price', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Max Price'
                )
                ->addColumn(
                    'shipping_days', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Shipping Days'
                )
                ->addColumn(
                    'shipping_lable', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Shipping Lable'
                )
                ->addColumn(
                    'shipping_price_percent', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Shipping Price Percent'
                )
                ->addColumn(
                'recommended', Table::TYPE_SMALLINT, null, ['nullable' => false, 'default' => '0'], 'Recommended'
            );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
