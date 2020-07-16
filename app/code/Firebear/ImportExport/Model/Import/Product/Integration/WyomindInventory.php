<?php
/**
 * WyomindInventory
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Exception;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor\Proxy as SkuProcessorProxy;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStateInterface\Proxy as StockStateInterfaceProxy;
use Magento\CatalogInventory\Model\StockRegistry\Proxy as StockRegistryProxy;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Wyomind\AdvancedInventory\Api\StockRepositoryInterface;
use Wyomind\AdvancedInventory\Model\Stock;
use Wyomind\AdvancedInventory\Model\StockFactory;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection as PosCollection;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory as PosCollectionFactory;
use function array_sum;
use function class_exists;
use function interface_exists;

/**
 * Class WyomindInventory
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
class WyomindInventory extends AbstractIntegration
{
    /** @var StockFactory */
    private $stockModelFactory;
    /** @var StockRepositoryInterface */
    private $stockRepoInterface;
    /** @var PosCollectionFactory */
    private $posCollectionFactory;

    /**
     * WyomindInventory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $_dataSourceModel
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\CatalogInventory\Api\StockStateInterface\Proxy $stockItem
     * @param \Magento\CatalogInventory\Model\StockRegistry\Proxy $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor\Proxy $skuProcessor
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ObjectManager $objectManager,
        ResourceModelData $_dataSourceModel,
        ConsoleOutput $output,
        LoggerInterface $logger,
        StockStateInterfaceProxy $stockItem,
        StockRegistryProxy $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        ResourceConnection $resource,
        SkuProcessorProxy $skuProcessor,
        ProductMetadataInterface $productMetadata
    ) {
        parent::__construct(
            $objectManager,
            $_dataSourceModel,
            $output,
            $logger,
            $stockItem,
            $stockRegistry,
            $stockConfiguration,
            $resource,
            $skuProcessor,
            $productMetadata
        );
        if (class_exists(Stock::class)) {
            $this->stockModelFactory = $objectManager->create(StockFactory::class);
        }
        if (class_exists(PosCollection::class)) {
            $this->posCollectionFactory = $objectManager->create(PosCollectionFactory::class);
        }
        if (interface_exists(StockRepositoryInterface::class)) {
            $this->stockRepoInterface = $objectManager->create(StockRepositoryInterface::class);
        }
    }

    /**
     * @param bool $verbosity
     *
     * @return mixed|void
     */
    public function importData($verbosity = false)
    {
        if ($verbosity) {
            $this->getOutput()->setVerbosity($verbosity);
        }
        $this->addLogWriteln(__('Wyomind Inventory Integration'), $this->getOutput());
        $this->_construct();
        try {
            while ($bunch = $this->getDataSourceModel()->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    if (isset($rowData[Product::COL_SKU])) {
                        $productIdFromSku = (int)$this->getProductId($rowData[Product::COL_SKU]);
                        $this->addLogWriteln(
                            __('--------Start Update Stock for product %1 ----------', $rowData[Product::COL_SKU]),
                            $this->getOutput(),
                            'info'
                        );
                        $this->updateWyomindAI($rowData, $productIdFromSku, $rowData[Product::COL_SKU]);
                        $this->addLogWriteln(
                            __('--------End Update Stock for product %1 ----------', $rowData[Product::COL_SKU]),
                            $this->getOutput(),
                            'info'
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
        }
    }

    /**
     * @param array $rowData
     * @param int $productId
     * @param string $productSku
     */
    private function updateWyomindAI(array $rowData, int $productId, string $productSku): void
    {
        $totalQty = [];
        foreach ($rowData as $attrCode => $attrValue) {
            if (preg_match('/^(wyomind\|).+/', $attrCode)) {
                $wareHouseData = explode('|', $attrCode);
                $warehouseId = '';
                $field = 'qty';
                foreach ($wareHouseData as $wValue) {
                    $val = explode(':', $wValue);
                    if ($val[0] === 'id') {
                        $warehouseId = $val[1];
                    }
                    if ($val[0] === 'field') {
                        $field = $val[1];
                    }
                }

                $pos = $this->getPosCollection()->getPlace($warehouseId);
                if ($field === 'qty' && ($pos->count() > 0)) {
                    $pos = $pos->getFirstItem()->getData();
                    try {
                        $this->getStockInterface()
                            ->updateStock(
                                $productId,
                                $rowData['wyomind_multistock_enabled'] ?? 1,
                                $warehouseId,
                                $rowData['manage_stock'] ?? 1,
                                $attrValue,
                                $rowData['allow_backorders'] ?? 0,
                                $rowData['use_config_backorders'] ?? 0
                            );
                        $stock = $this->getStockModel()->getStockSettings($productId, false, [$warehouseId]);
                        $stockId = 'getStockId' . $warehouseId;
                        $data = [
                            'id' => $stock->$stockId(),
                            'item_id' => $stock->getItemId(),
                            'place_id' => $warehouseId,
                            'product_id' => $productId,
                            'quantity_in_stock' => $attrValue,
                        ];
                        if (!empty($data)) {
                            $totalQty[] = $attrValue;
                            $this->getStockModel()->load($data['id'])->setData($data)->save();
                            $this->addLogWriteln(
                                __('Stock Update for Warehouse %1 with qty %2', $pos['store_code'], $attrValue),
                                $this->getOutput(),
                                'info'
                            );
                        }
                    } catch (Exception $exception) {
                        $this->addLogWriteln(
                            $exception->getMessage(),
                            $this->getOutput(),
                            'error'
                        );
                    }
                }
            }
        }
        if (!empty($totalQty)) {
            try {
                $stockTable = $this->getConnection()->getTableName('cataloginventory_stock_item');
                $select = $this->getConnection()->select()
                    ->from($stockTable)
                    ->where('product_id = ?', $productId);
                $stockItemId = $this->getConnection()->fetchRow($select)['item_id'] ?? 0;
                if ($stockItemId > 0) {
                    $stockItem = $this->stockRegistry->getStockItem(
                        $productId,
                        $this->stockConfiguration->getDefaultScopeId()
                    );
                    $stockItem->setItemId($stockItemId)->setQty(array_sum($totalQty));
                    $this->stockRegistry->updateStockItemBySku($productSku, $stockItem);
                    $this->addLogWriteln(
                        __('update default stock table %1', $stockTable),
                        $this->getOutput(),
                        'info'
                    );
                }
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (CouldNotSaveException $exception) {
                $this->addLogWriteln(
                    $exception->getMessage(),
                    $this->getOutput(),
                    'error'
                );
            } catch (Exception $exception) {
                $this->addLogWriteln(
                    $exception->getMessage(),
                    $this->getOutput(),
                    'error'
                );
            }
        }
    }

    /**
     * @return PosCollection
     */
    private function getPosCollection(): PosCollection
    {
        return $this->posCollectionFactory->create();
    }

    /**
     * @return \Wyomind\AdvancedInventory\Api\StockRepositoryInterface
     */
    private function getStockInterface(): StockRepositoryInterface
    {
        return $this->stockRepoInterface;
    }

    /**
     * @return \Wyomind\AdvancedInventory\Model\Stock
     */
    private function getStockModel(): Stock
    {
        return $this->stockModelFactory->create();
    }
}
