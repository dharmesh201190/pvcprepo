<?php
namespace LR\Serviceupgrade\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $tableName = $setup->getTable('lr_serviceupgrade');
            $connection = $setup->getConnection();
            $connection->addColumn(
                $tableName, 'sku', ['type' => Table::TYPE_TEXT, 'nullable' => false, 'afters' => 'range_max_price', 'length' => 255, 'default' => '', 'comment' => 'Product SKU']
            );
        }

        $installer = $setup;
         if (version_compare($context->getVersion(), '0.0.3', '<')) {
            if (!$installer->tableExists('lr_holidays')) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable('lr_holidays'))
                    ->addColumn(
                        'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Id'
                    )
                    ->addColumn(
                        'date', Table::TYPE_DATE, null, ['nullable' => false], 'DATE'
                    )
                    ->addColumn(
                        'holiday_title', Table::TYPE_TEXT, 255, ['nullable' => false], 'Holiday title'
                    );
                $installer->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '0.0.4', '<')) {
            if (!$installer->tableExists('lr_materialtype')) {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable('lr_materialtype'))
                    ->addColumn(
                        'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Id'
                    )
                    ->addColumn(
                        'material_type', Table::TYPE_TEXT, 255, ['nullable' => false], 'Material type'
                    )
                    ->addColumn(
                        'max_width', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Max Width'
                    )
                    ->addColumn(
                        'max_height', Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Max Height'
                    )
                    ->addColumn(
                        'custom_message', Table::TYPE_TEXT, 255, ['nullable' => false], 'Custom message'
                    );
                $installer->getConnection()->createTable($table);
            }
        }

        $setup->endSetup();
    }
}
