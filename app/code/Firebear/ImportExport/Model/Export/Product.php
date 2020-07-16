<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use DateTime;
use Exception;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Traits\Export\Products as ExportProductTrait;
use IntlDateFormatter;
use MageArray\MaMarketPlace\Helper\Data as MaMarketPlaceHelperData;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as WebkulHelperData;
use Wyomind\AdvancedInventory\Model\Stock as AdvancedInventoryStockModel;
use Wyomind\AdvancedInventory\Model\StockFactory as AdvancedInventoryStockModelFactory;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection as PosCollection;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory as PosCollectionFactory;
use function array_merge;
use function array_sum;
use function class_exists;

class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    use ExportProductTrait;

    protected $headColumns;

    protected $additional;

    private $userDefinedAttributes = [];

    protected $keysAdditional;

    /** @var \Magento\Framework\Module\Manager  */
    protected $moduleManager;

    /** @var string  */
    protected $multipleValueSeparator;
    /**
     * @var AdvancedInventoryStockModelFactory
     */
    private $advancedStockModelFactory;
    /** @var PosCollectionFactory  */
    private $posCollectionFactory;

    /**
     * Product constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer
     * @param Product\Additional $additional
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $dateAttrCodes
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        CollectionFactory $attributeColFactory,
        Factory $_typeFactory,
        LinkTypeProvider $linkTypeProvider,
        RowCustomizerInterface $rowCustomizer,
        Product\Additional $additional,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        array $dateAttrCodes = []
    ) {
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes
        );
        $this->additional = $additional;
        $this->moduleManager = $moduleManager;
        $this->multipleValueSeparator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
        if (class_exists(AdvancedInventoryStockModel::class)) {
            $this->advancedStockModelFactory = $objectManager->create(AdvancedInventoryStockModelFactory::class);
        }
        if (class_exists(PosCollection::class)) {
            $this->posCollectionFactory = $objectManager->create(PosCollectionFactory::class);
        }
    }

    /**
     * @return AdvancedInventoryStockModel
     */
    private function getAdvancedStockModel(): AdvancedInventoryStockModel
    {
        return $this->advancedStockModelFactory->create();
    }

    /**
     * @return PosCollection
     */
    private function getPosCollection(): PosCollection
    {
        return $this->posCollectionFactory->create();
    }

    /**
     * @param $productIds
     * @return array
     */
    protected function getMageStoreInventoryStocks($productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        $warehouseData = ObjectManager::getInstance()->get(
            WarehouseStockRegistryInterface::class
        );
        $warehouseData = $warehouseData->getStocksWarehouses($productIds)->getData();
        $stockItemRows = [];
        foreach ($warehouseData as $stockItemRow) {
            $productId = $stockItemRow['product_id'];
            unset(
                $stockItemRow['item_id'],
                $stockItemRow['product_id'],
                $stockItemRow['low_stock_date'],
                $stockItemRow['stock_id'],
                $stockItemRow['stock_status_changed_auto']
            );
            $stockItemRows[$productId] = $stockItemRow;
        }
        return $stockItemRows;
    }

    /**
     * @return array
     */
    protected function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);
            if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')) {
                /** @var \MageArray\MaMarketPlace\Helper\Data $mageArrayHelper */
                $mageArrayHelper = ObjectManager::getInstance()->get(MaMarketPlaceHelperData::class);
            }

            if ($this->moduleManager->isEnabled('Webkul_Marketplace')) {
                /** @var \Webkul\Marketplace\Helper\Data $webKulHelperManager */
                $webKulHelperManager = ObjectManager::getInstance()->get(WebkulHelperData::class);
            }

            if ($this->moduleManager->isEnabled('Magestore_InventorySuccess')) {
                $stockItemRows = $this->getMageStoreInventoryStocks($productIds);
            } elseif ($this->moduleManager->isEnabled('Wyomind_AdvancedInventory')
                && $this->moduleManager->isEnabled('Wyomind_PointOfSale')
            ) {
                $stockItemRows = $this->getWyomindAdvancedInventory($productIds);
            } else {
                $stockItemRows = $this->prepareCatalogInventory($productIds);
            }

            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->setAddHeaderColumns($stockItemRows);
            $prevData = [];
            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if (isset($stockItemRows[$productId])) {
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')) {
                        $dataRow['vendor_id'] = $mageArrayHelper->getVendorByProductId($productId)->getUserId();
                    }
                    if ($this->moduleManager->isEnabled('Webkul_Marketplace')) {
                        $dataRow['vendor_id'] = $webKulHelperManager->getSellerProductDataByProductId($productId)
                            ->getFirstItem()->getId();
                    }
                    $this->appendMultirowData($dataRow, $multirawData);

                    if ($dataRow) {
                        if (isset($dataRow[self::COL_TYPE]) && $dataRow[self::COL_TYPE] == 'bundle' &&
                            Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR !== $this->multipleValueSeparator) {
                            $dataRow['bundle_values'] = str_replace(
                                Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                                $this->multipleValueSeparator,
                                $dataRow['bundle_values']
                            );
                        }
                        if (!empty($prevData)) {
                            if (isset($prevData['sku']) && isset($dataRow['sku'])) {
                                if ($prevData['sku'] == $dataRow['sku']) {
                                    $dataRow = array_merge($prevData, $dataRow);
                                }
                            }
                        }
                        $exportData[] = $dataRow;
                    }
                    $prevData = $dataRow;
                }
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData, 'product_id');
        $this->addHeaderColumns();
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
    }

    /**
     * @param $productIds
     *
     * @return array
     */
    protected function getWyomindAdvancedInventory($productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $stockItemRows = $this->prepareCatalogInventory($productIds);
        try {
            foreach ($productIds as $productId) {
                $_warehouseStock = [];
                $pointOfSales = $this->getPosCollection();
                $totalQty = [];
                /** @var \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale */
                foreach ($pointOfSales as $pointOfSale) {
                    /** @var \Magento\Framework\DataObject $stocks */
                    $stocks = $this->getAdvancedStockModel()
                        ->getStockSettings($productId, null, [$pointOfSale->getPlaceId()]);
                    $getQuantity = 'getQuantity' . $pointOfSale->getPlaceId();
                    $storeCode = 'wyomind|id:' . $pointOfSale->getPlaceId() . '|field:qty|code:'
                        . $pointOfSale->getStoreCode();
                    $_warehouseStock[$storeCode] = $stocks->$getQuantity();
                    $totalQty[] = $stocks->$getQuantity();
                }
                $stockItemRows[$productId] += $_warehouseStock;
                $stockItemRows[$productId]['qty'] = array_sum($totalQty);
            }
        } catch (Exception $exception) {
            $this->addLogWriteln(
                $exception->getMessage(),
                $this->getOutput(),
                'error'
            );
        }
        return $stockItemRows;
    }

    protected function _customHeadersMapping($rowData)
    {
        $rowData = parent::_customHeadersMapping($rowData);

        return ($this->_parameters[Processor::ALL_FIELDS]) ? $this->_headerColumns : array_unique($rowData);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export()
    {
        $this->keysAdditional = [];

        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $counts = 0;
        if (isset($this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator'])
            && $this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator']) {
            $this->multipleValueSeparator = $this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator'];
        }
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            if (isset($this->_parameters[Processor::LAST_ENTITY_ID])
                && $this->_parameters[Processor::LAST_ENTITY_ID] > 0
                && $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
            ) {
                $entityCollection->addFieldToFilter(
                    'entity_id',
                    ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
                );
            }
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            $exportData = $this->customBunchesData($exportData);
            foreach ($exportData as $dataRow) {
                if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
                    $this->lastEntityId = $dataRow['product_id'];
                }
                $writer->writeRow($this->_customFieldsMapping($dataRow));
                $counts++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function _customFieldsMapping($rowData)
    {

        $headerColumns = $this->_getHeaderColumns();

        $rowData = parent::_customFieldsMapping($rowData);
        if (count($headerColumns) != count(array_keys($rowData))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                $fieldCode = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : null;
                if ($fieldCode && isset($rowData[$fieldCode])) {
                    $newData[$code] = $rowData[$fieldCode];
                } else {
                    if (!isset($rowData[$code])) {
                        $newData[$code] = '';
                    } else {
                        $newData[$code] = $rowData[$code];
                    }
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!isset($this->_parameters[Export::FILTER_ELEMENT_GROUP])
            || !is_array($this->_parameters[Export::FILTER_ELEMENT_GROUP])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Export::FILTER_ELEMENT_GROUP];
        }

        $collection = Export\Entity\AbstractEntity::_prepareEntityCollection($collection);

        foreach ($this->additional->fields as $field) {
            if (isset($exportFilter[$field]) && !empty($exportFilter[$field])) {
                if ($field == 'store') {
                    $collection->addStoreFilter($exportFilter['store']);
                } else {
                    $collection->getSelect()->where(
                        $this->additional->convertFields($field) . "=?",
                        $exportFilter[$field]
                    );
                }
            }
        }

        if (isset($exportFilter['category_ids']) &&
            is_array($exportFilter['category_ids']) &&
            count($exportFilter['category_ids']) == 2) {
            $from = array_shift($exportFilter['category_ids']);
            $to = array_shift($exportFilter['category_ids']);

            $collection->joinTable(
                ['cp' => $collection->getResource()->getTable('catalog_category_product')],
                'product_id = entity_id',
                ['category_id']
            );
            if (is_numeric($from)) {
                $collection->getSelect()->where('cp.category_id >= ?', $from);
            }
            if (is_numeric($to)) {
                $collection->getSelect()->where('cp.category_id <= ?', $to);
            }
        }

        return $collection;
    }

    protected function collectMultirawData()
    {
        $data = [];
        $productIds = [];
        $rowWebsites = [];
        $rowCategories = [];
        $productLinkIds = [];

        $entityCollection = $this->_getEntityCollection();
        $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
        $entityCollection->addCategoryIds()->addWebsiteNamesToResult();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($entityCollection as $item) {
            $productLinkIds[] = $item->getData($this->getProductEntityLinkField());
            $productIds[] = $item->getId();
            $rowWebsites[$item->getId()] = array_intersect(
                array_keys($this->_websiteIdToCode),
                $item->getWebsites()
            );
            $rowCategories[$item->getId()] = array_combine($item->getCategoryIds(), $item->getCategoryIds());
        }
        $entityCollection->clear();

        $categoryIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
        $categoryIds = array_combine($categoryIds, $categoryIds);
        foreach ($rowCategories as &$categories) {
            $categories = array_intersect_key($categories, $categoryIds);
        }

        $data['rowWebsites'] = $rowWebsites;
        $data['rowCategories'] = $rowCategories;

        $data['linksRows'] = $this->prepareLinks($productLinkIds);

        $data['customOptionsData'] = $this->getCustomOptionsData($productLinkIds);

        return $data;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function fieldsCatalogInventory()
    {
        $fields = $this->_connection->describeTable($this->_itemFactory->create()->getMainTable());
        $rows = [];
        $row = [];
        unset(
            $fields['item_id'],
            $fields['product_id'],
            $fields['low_stock_date'],
            $fields['stock_id'],
            $fields['stock_status_changed_auto']
        );
        foreach ($fields as $key => $field) {
            $row[$key] = $key;
        }

        $rows[] = $row;
        return $rows;
    }

    protected function collectRawData()
    {
        $data = [];
        $items = $this->fireloadCollection();
        foreach ($items as $itemId => $itemByStore) {

            /**
             * @var int $itemId
             * @var ProductEntity $item
             */
            foreach ($this->getStores() as $storeId => $storeCode) {
                if (!isset($itemByStore[$storeId])) {
                    continue;
                }
                $item = $itemByStore[$storeId];
                $addtionalFields = [];
                $additionalAttributes = [];
                $productLinkId = $item->getData($this->getProductEntityLinkField());
                foreach ($this->_getExportAttrCodes() as $attrCodes) {
                    $attrValue = $item->getData($attrCodes);
                    $attrValue = str_replace(["\r\n", "\n\r", "\n", "\r"], '', $attrValue);
                    if (!$this->isValidAttributeValue($attrCodes, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$attrCodes][$attrValue])
                        && !empty($this->_attributeValues[$attrCodes])
                    ) {
                        $attrValue = $this->_attributeValues[$attrCodes][$attrValue];
                    }
                    $fieldName = isset($this->_fieldsMap[$attrCodes]) ? $this->_fieldsMap[$attrCodes] : $attrCodes;

                    if ($this->_attributeTypes[$attrCodes] == 'datetime') {
                        if (in_array($attrCodes, $this->dateAttrCodes) ||
                            in_array($attrCodes, $this->userDefinedAttributes)) {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new DateTime($attrValue),
                                    IntlDateFormatter::SHORT,
                                    IntlDateFormatter::NONE,
                                    null,
                                    date_default_timezone_get()
                                );
                        } else {
                            $attrValue = $this->_localeDate
                                ->formatDateTime(
                                    new DateTime($attrValue),
                                    IntlDateFormatter::SHORT,
                                    IntlDateFormatter::SHORT
                                );
                        }
                    }

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) {
                        continue;
                    }

                    if ($this->_attributeTypes[$attrCodes] !== 'multiselect') {
                        if (is_scalar($attrValue)) {
                            if (!in_array($fieldName, $this->_getExportMainAttrCodes())) {
                                $additionalAttributes[$fieldName] = $fieldName .
                                    ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->wrapValue($attrValue);
                                if ($this->checkDivideofAttributes()) {
                                    $addtionalFields[$fieldName] = $attrValue;
                                    if (!in_array($fieldName, $this->keysAdditional)) {
                                        $this->keysAdditional[] = $fieldName;
                                    }
                                }
                            }
                            $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                        }
                    } else {
                        $this->collectMultiselectValues($item, $attrCodes, $storeId);
                        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes])) {
                            $additionalAttributes[$attrCodes] = $fieldName .
                                ImportProduct::PAIR_NAME_VALUE_SEPARATOR . implode(
                                    ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                                    $this->wrapValue(
                                        $this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes]
                                    )
                                );
                            if ($this->checkDivideofAttributes()) {
                                if (!in_array($attrCodes, $this->keysAdditional)) {
                                    $this->keysAdditional[] = $attrCodes;
                                }
                                $addtionalFields[$attrCodes] =
                                    $this->collectedMultiselectsData[$storeId][$productLinkId][$attrCodes];
                            }
                        }
                    }
                }
                if (!empty($additionalAttributes)) {
                    $additionalAttributes = array_map('htmlspecialchars_decode', $additionalAttributes);
                    $data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES] =
                        implode($this->multipleValueSeparator, $additionalAttributes);
                } else {
                    unset($data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES]);
                }

                if (!empty($data[$itemId][$storeId]) || $this->hasMultiselectData($item, $storeId)) {
                    $attrSetId = $item->getAttributeSetId();
                    $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                    $data[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
                    $data[$itemId][$storeId][self::COL_TYPE] = $item->getTypeId();
                }
                if (!empty($addtionalFields)) {
                    foreach ($addtionalFields as $key => $value) {
                        $data[$itemId][$storeId][$key] = $value;
                    }
                }
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['store_id'] = $storeId;
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_link_id'] = $productLinkId;
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function collectMultiselectValues($item, $attrCode, $storeId)
    {
        $attrValue = $item->getData($attrCode);
        $optionIds = explode($this->multipleValueSeparator, $attrValue);
        $options = array_intersect_key(
            $this->_attributeValues[$attrCode],
            array_flip($optionIds)
        );
        $linkId = $item->getData($this->getProductEntityLinkField());
        if (!(isset($this->collectedMultiselectsData[Store::DEFAULT_STORE_ID][$linkId][$attrCode])
            && $this->collectedMultiselectsData[Store::DEFAULT_STORE_ID][$linkId][$attrCode] == $options)
        ) {
            $this->collectedMultiselectsData[$storeId][$linkId][$attrCode] = $options;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function optionRowToCellString($option)
    {
        $result = [];

        foreach ($option as $key => $value) {
            $result[] = $key . ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $value;
        }

        return implode($this->multipleValueSeparator, $result);
    }

    private function wrapValue(
        $value
    ) {
        if (!empty($this->_parameters[Export::FIELDS_ENCLOSURE])) {
            $wrap = function ($value) {
                return sprintf('"%s"', str_replace('"', '""', $value));
            };

            $value = is_array($value) ? array_map($wrap, $value) : $wrap($value);
        }

        return $value;
    }

    /**
     * @param $stockItemRows
     */
    protected function setAddHeaderColumns($stockItemRows)
    {
        $addData = [];

        if (!empty($stockItemRows)) {
            if (reset($stockItemRows)) {
                $addData = array_keys(end($stockItemRows));
                foreach ($addData as $key => $value) {
                    if (is_numeric($value)) {
                        unset($addData[$key]);
                    }
                }
            }
        }
        if (!$this->_headerColumns) {
            $this->_headerColumns = array_merge(
                [
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                [self::COL_ADDITIONAL_ATTRIBUTES],
                $addData,
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position',
                    'additional_images',
                    'additional_image_labels',
                    'hide_from_product_page',
                    'custom_options',
                ]
            );
            if ($this->moduleManager->isEnabled('MageArray_MaMarketPlace')
                || $this->moduleManager->isEnabled('Webkul_Marketplace')
            ) {
                $this->_headerColumns = array_merge(
                    $this->_headerColumns,
                    [
                        'vendor_id',
                    ]
                );
            }
        }
    }

    protected function addHeaderColumns()
    {
        if ($this->checkDivideofAttributes()) {
            $this->_headerColumns = array_merge($this->_headerColumns, $this->keysAdditional);
        }

        $this->_headerColumns = array_unique(array_merge(
            $this->_headerColumns,
            $this->_getExportAttrCodes()
        ));
    }

    protected function fireloadCollection()
    {
        $data = [];

        $collection = $this->_getEntityCollection();

        foreach (array_keys($this->getStores()) as $storeId) {
            $collection->clear();
            $collection->addStoreFilter($storeId);

            foreach ($collection as $itemId => $item) {
                $data[$itemId][$storeId] = $item;
            }
        }
        $collection->clear();

        return $data;
    }

    protected function checkDivideofAttributes()
    {
        return isset($this->_parameters[Processor::DIVIDED_ATTRIBUTES]) &&
            $this->_parameters[Processor::DIVIDED_ATTRIBUTES];
    }

    /**
     * @param array $dataRow
     * @param array $multiRawData
     *
     * @return array|null
     */
    protected function appendMultirowData(&$dataRow, &$multiRawData)
    {
        $pId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];

        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);

        $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $pId);
        if (!empty($multiRawData['rowWebsites'][$pId])) {
            $websiteCodes = [];
            foreach ($multiRawData['rowWebsites'][$pId] as $productWebsite) {
                $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
            }
            $dataRow[self::COL_PRODUCT_WEBSITES] =
                implode($this->multipleValueSeparator, $websiteCodes);
            $multiRawData['rowWebsites'][$pId] = [];
        }

        $multiRawData['mediaGalery'] = $this->getMediaGallery([$productLinkId]);
        if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
            $additionalImages = [];
            $additionalLabels = [];
            $additionalImageIsDisabled = [];
            foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                $additionalImages[] = $mediaItem['_media_image'];
                $additionalLabels[] = $mediaItem['_media_label'];
                if ($mediaItem['_media_is_disabled'] == true) {
                    $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                }
            }

            $dataRow['additional_images'] = implode(
                $this->multipleValueSeparator,
                $additionalImages
            );
            $dataRow['additional_image_labels'] = implode(
                $this->multipleValueSeparator,
                $additionalLabels
            );
            $dataRow['hide_from_product_page'] = implode(
                $this->multipleValueSeparator,
                $additionalImageIsDisabled
            );
            $multiRawData['mediaGalery'][$productLinkId] = [];
        }
        foreach ($this->_linkTypeProvider->getLinkTypes() as $typeName => $linkId) {
            if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                $colPrefix = $typeName . '_';
                $associations = [];
                foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                    if ($linkData['default_qty'] !== null) {
                        $skuItem = $linkData['sku']
                            . ImportProduct::PAIR_NAME_VALUE_SEPARATOR
                            . $linkData['default_qty'];
                    } else {
                        $skuItem = $linkData['sku'];
                    }
                    $associations[$skuItem] = $linkData['position'];
                }
                $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                asort($associations);
                $dataRow[$colPrefix . 'skus'] = implode(
                    $this->multipleValueSeparator,
                    array_keys($associations)
                );
                $dataRow[$colPrefix . 'position'] = implode(
                    $this->multipleValueSeparator,
                    array_values($associations)
                );
            }
        }
        $dataRow = $this->rowCustomizer->addData($dataRow, $pId);

        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productLinkId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey])) {
                    $dataRow[$attrKey] =
                        implode(
                            $this->multipleValueSeparator,
                            $this->collectedMultiselectsData[$storeId][$productLinkId][$attrKey]
                        );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $customOptionsRows =
                $multiRawData['customOptionsData'][$productLinkId][$storeId];
            $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
            $customOptions =
                implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);

            $dataRow = array_merge(
                $dataRow,
                ['custom_options' => $customOptions]
            );
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;

        return $dataRow;
    }

    protected function _getExportAttrCodes()
    {
        if (null === self::$attrCodes) {
            $attrCodes = [];
            $parameters = $this->_parameters;

            if (isset($parameters[Processor::ALL_FIELDS]) && $parameters[Processor::ALL_FIELDS] &&
                isset($parameters[Processor::LIST_DATA]) && is_array($parameters[Processor::LIST_DATA])) {
                $attrCodes = array_merge($this->_permanentAttributes, $parameters[Processor::LIST_DATA]);
            } else {
                foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
                    $attrCodes[] = $attribute->getAttributeCode();
                }
            }

            self::$attrCodes = $attrCodes;
        }

        return self::$attrCodes;
    }

    /**
     * {@inheritDoc}
     */
    protected function updateDataWithCategoryColumns(&$dataRow, &$rowCategories, $productId)
    {
        if (!isset($rowCategories[$productId])) {
            return false;
        }
        $categories = [];
        foreach ($rowCategories[$productId] as $categoryId) {
            $categoryPath = $this->_rootCategories[$categoryId];
            if (isset($this->_categories[$categoryId])) {
                $categoryPath .= '/' . $this->_categories[$categoryId];
            }
            $categories[] = $categoryPath;
        }
        $dataRow[self::COL_CATEGORY] = implode($this->multipleValueSeparator, $categories);
        unset($rowCategories[$productId]);

        return true;
    }

    /**
     * Filter by stores
     *
     * @return array
     */
    protected function getStores()
    {
        $stores = [];
        if (isset($this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'])
            && is_array($this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'])
        ) {
            $storeIds = $this->_parameters[Processor::BEHAVIOR_DATA]['store_ids'];
            foreach ($this->_storeIdToCode as $id => $code) {
                if (in_array($id, $storeIds)) {
                    $stores[$id] = $code;
                }
            }
        } else {
            $stores = $this->_storeIdToCode;
        }

        return $stores;
    }
}
