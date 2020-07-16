<?php
/**
 * MageArrayMarketplace
 *
 * @copyright Copyright © 2018 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Integration;

use Exception;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\ResourceModel\Import\Data as ResourceModelData;
use MageArray\MaMarketPlace\Helper\Data;
use MageArray\PriceComparison\Model\PricecomparisonFactory;
use MageArray\PriceComparison\Model\ResourceModel\Pricecomparison\CollectionFactory as PriceComparisonCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor\Proxy as SkuProcessorProxy;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStateInterface\Proxy as StockStateInterfaceProxy;
use Magento\CatalogInventory\Model\StockRegistry\Proxy as StockRegistryProxy;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use function explode;

/**
 * Class MageArrayMarketplace
 * @package Firebear\ImportExport\Model\Import\Product\Integration
 */
class MageArrayMarketplace extends AbstractIntegration
{
    const MAGE_PRICE_COMPARE = 'magearray_price_compare';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * MageArrayMarketplace constructor.
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
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
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
        ProductMetadataInterface $productMetadata,
        ProductRepositoryInterface $productRepository
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
        $this->productRepository = $productRepository;
    }

    public function importData($verbosity = false)
    {
        if ($verbosity) {
            $this->getOutput()->setVerbosity($verbosity);
        }
        $this->addLogWriteln(__('MageArray Marketplace Integration'), $this->getOutput());
        $this->_construct();
        try {
            /** @var \MageArray\MaMarketPlace\Helper\Data $mageArrayHelper */
            $mageArrayHelper = $this->getObjectManager()
                ->get(Data::class);
            /** @var \MageArray\MaMarketPlace\Model\Product $mageArrayProduct */
            $mageArrayProduct = $this->getObjectManager()
                ->get(\MageArray\MaMarketPlace\Model\Product::class);
            while ($bunch = $this->getDataSourceModel()->getNextBunch()) {
                foreach ($bunch as $rowData) {
                    if (isset($rowData[Product::COL_SKU], $rowData[Product::VENDOR_ID])
                        && $mageArrayHelper->getVendorByUserId($rowData[Product::VENDOR_ID])->getIsActive()
                    ) {
                        $product = $this->productRepository->get($rowData[Product::COL_SKU]);
                        $productIdFromSku = (int)$this->getProductId($rowData[Product::COL_SKU]);
                        $stockQty = $this->stockItem
                            ->getStockQty($productIdFromSku, $product->getStore()->getWebsiteId());
                        $vendorId = (int)$mageArrayHelper->getVendorByProductId($productIdFromSku)->getUserId();
                        if ($vendorId === 0) {
                            $this->addLogWriteln(
                                __('Assign Products to User ID %1', $rowData[Product::VENDOR_ID]),
                                $this->getOutput()
                            );
                            $mageArrayProduct->assignProduct($rowData[Product::VENDOR_ID], $productIdFromSku);
                        } elseif ($vendorId !== (int)$rowData[Product::VENDOR_ID] || $vendorId > 0) {
                            $this->addLogWriteln(
                                __(
                                    'Product already assigned to user id %1 and cannot be assigned to user id %2 ' .
                                    'for product sku %3',
                                    $vendorId,
                                    $rowData[Product::VENDOR_ID],
                                    $rowData[Product::COL_SKU]
                                ),
                                $this->getOutput()
                            );
                        }

                        if (isset($rowData[self::MAGE_PRICE_COMPARE])) {
                            try {
                                $this->importPriceCompare(
                                    $rowData[self::MAGE_PRICE_COMPARE],
                                    $productIdFromSku,
                                    $stockQty,
                                    $vendorId,
                                    $mageArrayHelper
                                );
                            } catch (Exception $e) {
                                $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->getOutput(), 'error');
        }
    }

    /**
     * @param $priceArray
     * @param $productId
     * @param $stockQty
     * @param $vendorId
     * @param \MageArray\MaMarketPlace\Model\Product $mageArrayHelper
     */
    protected function importPriceCompare($priceArray, $productId, $stockQty, $vendorId, $mageArrayHelper): void
    {
        /** @var \MageArray\PriceComparison\Model\PricecomparisonFactory $pricecomparisonFactory */
        $pricecomparisonFactory = $this->getObjectManager()
            ->get(PricecomparisonFactory::class);
        /** @var PriceComparisonCollectionFactory $pricecomparisonCollectionFactory */
        $pricecomparisonCollectionFactory = $this->getObjectManager()
            ->get(PriceComparisonCollectionFactory::class);
        foreach (explode('|', $priceArray) as $vendorPriceData) {
            $priceData = [];
            $userId = 0;
            foreach (explode(',', $vendorPriceData) as $vendorData) {
                $_vendorPrice = explode('=', $vendorData);
                $priceData[$_vendorPrice[0]] = $_vendorPrice[1];
                if ($_vendorPrice[0] == 'vendor_id') {
                    $userId = $_vendorPrice[1];
                    $priceData[$_vendorPrice[0]] = $mageArrayHelper->getVendorByUserId($userId)->getVendorId();
                }
            }
            $qty = $priceData['qty'] ?? 0;
            $qty += $stockQty;
            $collection = $pricecomparisonCollectionFactory->create();
            $collection->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('vendor_id', $priceData['vendor_id']);
            if (!$collection->getSize() && $vendorId != $userId) {
                $this->addLogWriteln(
                    __('Price Compare added for vendor %1', $vendorId),
                    $this->getOutput(),
                    'info'
                );
                $_priceCompareModel = $pricecomparisonFactory->create();
                $_priceCompareModel->setData($priceData)
                    ->setProductId($productId)
                    ->setQty($qty)
                    ->save();
            }
        }
    }
}
