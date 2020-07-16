<?php
/**
 * AbstractIntegration
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use Firebear\ImportExport\Traits\Import\Entity;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor\Proxy as SkuProcessorProxy;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStateInterface\Proxy as StockStateInterfaceProxy;
use Magento\CatalogInventory\Model\StockRegistry\Proxy as StockRegistryProxy;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class AbstractIntegration
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
abstract class AbstractIntegration
{
    use Entity;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockItem;

    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;
    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor
     */
    protected $skuProcessor;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var $this
     */
    protected $oldSku;
    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * AbstractIntegration constructor.
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
        $this->objectManager = $objectManager;
        $this->_dataSourceModel = $_dataSourceModel;
        $this->output = $output;
        $this->_logger = $logger;
        $this->stockItem = $stockItem;
        $this->stockRegistry = $stockRegistry;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->skuProcessor = $skuProcessor;
        $this->productMetadata = $productMetadata;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @return ResourceModelData
     */
    public function getDataSourceModel(): ResourceModelData
    {
        return $this->_dataSourceModel;
    }

    /**
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $dataSourceModel
     *
     * @return \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    public function setDataSourceModel(ResourceModelData $dataSourceModel): ResourceModelData
    {
        return $this->_dataSourceModel = $dataSourceModel;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }

    /**
     * @param string|bool $verbosity
     *
     * @return mixed
     */
    abstract public function importData($verbosity = false);

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     *
     * @return array
     */
    protected function getExistingSku(string $sku): array
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $sku = strtolower($sku);
        }
        return $this->oldSku[$sku];
    }

    /**
     * Initialize old skus
     */
    protected function _construct(): void
    {
        $this->oldSku = $this->skuProcessor->getOldSkus();
    }

    /**
     * @param string $sku
     *
     * @return mixed
     */
    protected function getProductId(string $sku)
    {
        return $this->getExistingSku($sku)['entity_id'];
    }
}
