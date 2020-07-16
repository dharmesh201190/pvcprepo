<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import;

class Category extends \Magento\ImportExport\Model\Export\Entity\AbstractEntity
{
    use ExportTrait;

    /**
     * @var Collection
     */
    protected $entityCollection;

    /**
     * @var CollectionFactory
     */
    protected $entityCollectionFactory;

    /**
     * @var \Magento\ImportExport\Model\Export\ConfigInterface
     */
    protected $exportConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory
     */
    protected $attributeColFactory;

    /**
     * Attribute types
     *
     * @var array
     */
    protected $attributeTypes = [];

    /**
     * Website ID-to-code.
     *
     * @var array
     */
    protected $websiteIdToCode = [];

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory
     */
    protected $typeCollection;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $collectionAttr;

    private $userDefinedAttributes = [];

    protected $headerColumns = [];

    protected $fieldsMap = [];

    protected $dateAttrCodes = [
        'special_from_date',
        'special_to_date',
        'news_from_date',
        'news_to_date',
        'custom_design_from',
        'custom_design_to'
    ];

    protected $customAttr = [
        'custom_apply_to_products',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'custom_use_parent_settings',
        'description'
    ];

    protected $closedAttr = [
        'all_children',
        'children',
        'children_count',
        'level'
    ];

    /**
     * Items per page for collection limitation
     *
     * @var int|null
     */
    protected $itemsPerPage = null;

    /**
     * Category constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionAttrFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $config,
        ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionAttrFactory
    ) {
        $this->_logger = $logger;
        $this->entityCollectionFactory = $collectionFactory;
        $this->exportConfig = $exportConfig;
        $this->categoryFactory = $categoryFactory;
        $this->attributeColFactory = $attributeColFactory;
        $this->typeCollection = $typeCollection;
        $this->collectionAttr = $collectionAttrFactory;
        parent::__construct($localeDate, $config, $resource, $storeManager);

        $this->initAttributes()
            ->initWebsites();
        $this->getFieldsForExport();
    }

    public function _getHeaderColumns()
    {
        return $this->customHeadersMapping($this->headerColumns);
    }

    protected function customHeadersMapping($rowData)
    {
        foreach ($rowData as $key => $fieldName) {
            if (isset($this->fieldsMap[$fieldName])) {
                $rowData[$key] = $this->fieldsMap[$fieldName];
            }
        }

        return ($this->_parameters['all_fields']) ? $this->headerColumns : array_unique($rowData);
    }

    protected function setHeaderColumns($data)
    {
        if (!$this->headerColumns) {
            $this->headerColumns = array_merge(
                [
                    'name',
                    'image',
                    'url_path'
                ],
                $data
            );
        }
    }

    protected function _getEntityCollection($resetCollection = false)
    {

        if ($resetCollection || empty($this->entityCollection)) {
            $this->entityCollection = $this->entityCollectionFactory->create();
        }

        return $this->entityCollection;
    }

    protected function getItemsPerPage()
    {
        if ($this->itemsPerPage === null) {
            $memoryLimitConfigValue = trim(ini_get('memory_limit'));
            $lastMemoryLimitLetter = strtolower($memoryLimitConfigValue[strlen($memoryLimitConfigValue) - 1]);
            $memoryLimit = (int)$memoryLimitConfigValue;
            switch ($lastMemoryLimitLetter) {
                case 'g':
                    $memoryLimit *= 1024;
                //next
                case 'm':
                    $memoryLimit *= 1024;
                //next
                case 'k':
                    $memoryLimit *= 1024;
                    break;
                default:
                    $memoryLimit = 250000000;
            }

            $memoryPerProduct = 500000;
            $memoryUsagePercent = 0.8;
            $minProductsLimit = 500;
            $maxProductsLimit = 5000;

            $this->itemsPerPage = (int) (
                ($memoryLimit * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct
            );
            if ($this->itemsPerPage < $minProductsLimit) {
                $this->itemsPerPage = $minProductsLimit;
            }
            if ($this->itemsPerPage > $maxProductsLimit) {
                $this->itemsPerPage = $maxProductsLimit;
            }
        }

        return $this->itemsPerPage;
    }

    protected function paginateCollection($page, $pageSize)
    {
        $this->_getEntityCollection()->setPage($page, $pageSize);
    }

    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $counts = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $this->_prepareEntityCollection($entityCollection);
            if (isset($this->_parameters['last_entity_id'])
                && $this->_parameters['last_entity_id'] > 0
                && $this->_parameters['enable_last_entity_id'] > 0
            ) {
                $entityCollection->addFieldToFilter(
                    'entity_id',
                    ['gt' => $this->_parameters['last_entity_id']]
                );
            }
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                if ($this->_parameters['enable_last_entity_id'] > 0) {
                    $this->lastEntityId = $dataRow['entity_id'];
                }
                $dd = $this->_customFieldsMapping($dataRow);
                $writer->writeRow($dd);
                $counts++;
            }

            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    protected function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();

            foreach ($rawData as $productId => $dataRow) {
                $exportData[] = $dataRow;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData, 'entity_id');

        $this->headerColumns = $this->changeHeaders($this->headerColumns);

        return $newData;
    }

    protected function collectRawData()
    {
        $data = [];
        $collection = $this->_getEntityCollection();

        foreach ($collection as $itemId => $item) {
            $path = [];
            foreach ($this->getParentCategories($item) as $cat) {
                if ($cat->getId() == \Magento\Catalog\Model\Category::TREE_ROOT_ID) {
                    continue;
                }
                $path[] = $cat->getName();
            }
            if (empty($path)) {
                $path[] = $item->getName();
            }
            $data[$itemId]['name'] = implode("/", $path);
            foreach ($this->_getExportAttrCodes() as $code) {
                if ($code == 'name' || in_array($code, $this->closedAttr)) {
                    continue;
                }
                $attrValue = $item->getData($code);
                if (!$this->isValidAttributeValue($code, $attrValue)) {
                    continue;
                }
                if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                    $attrValue = $this->_attributeValues[$code][$attrValue];
                }

                $fieldName = isset($this->fieldsMap[$code]) ? $this->fieldsMap[$code] : $code;
                if ($this->attributeTypes[$code] == 'datetime') {
                    if (in_array($code, $this->dateAttrCodes)
                        || in_array($code, $this->userDefinedAttributes)
                    ) {
                        $attrValue = $this->_localeDate->formatDateTime(
                            new \DateTime($attrValue),
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::NONE,
                            null,
                            date_default_timezone_get()
                        );
                    } else {
                        $attrValue = $this->_localeDate->formatDateTime(
                            new \DateTime($attrValue),
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::SHORT
                        );
                    }
                }

                if ($this->attributeTypes[$code] !== 'multiselect') {
                    if (is_scalar($attrValue)) {
                        if (in_array($fieldName, $this->customAttr)) {
                            $attrValue = addslashes($attrValue);
                        }
                        $data[$itemId][$fieldName] = htmlspecialchars_decode($attrValue);
                    }
                } else {
                    $data[$itemId][$fieldName] = $attrValue;
                }
            }
            $data[$itemId]['image'] = $item->getImageUrl();
            $data[$itemId]['entity_id'] = $item->getEntityId();
        }

        return $data;
    }

    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    protected function initAttributes()
    {
        foreach ($this->getAttributeCollection() as $attribute) {
            try {
                $this->_attributeValues[$attribute->getAttributeCode()] = $this->getAttributeOptions($attribute);
            } catch (\TypeError $exception) {
                // ignore exceptions connected with source models
                $this->_attributeValues[$attribute->getAttributeCode()] = [];
            }
            $this->attributeTypes[$attribute->getAttributeCode()] =
                \Magento\ImportExport\Model\Import::getAttributeType($attribute);
            if ($attribute->getIsUserDefined()) {
                $this->userDefinedAttributes[] = $attribute->getAttributeCode();
            }
        }

        return $this;
    }

    protected function initWebsites()
    {
        /** @var $website \Magento\Store\Model\Website */
        foreach ($this->_storeManager->getWebsites() as $website) {
            $this->websiteIdToCode[$website->getId()] = $website->getCode();
        }

        return $this;
    }

    public function getFieldsForExport()
    {
        $list = [];
        foreach ($this->getAttributeCollection() as $attribute) {
            if (!in_array($attribute->getAttributeCode(), $this->closedAttr)) {
                $list[] = $attribute->getAttributeCode();
            }
        }
        $this->setHeaderColumns($list);

        return array_unique($this->headerColumns);
    }

    public function getFieldsForFilter()
    {
        $options = [];
        $types = $this->typeCollection->create()->addFieldToFilter('entity_type_code', $this->getEntityTypeCode());
        if ($types->getSize()) {
            $collection = $this->collectionAttr->create()->addFieldToFilter(
                'entity_type_id',
                $types->getFirstItem()->getId()
            );
            foreach ($collection as $item) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getFrontendLabel() ? $item->getFrontendLabel() : $item->getAttributeCode()
                ];
            }
        }

        return [$this->getEntityTypeCode() => $options];
    }

    public function getAttributeCollection()
    {
        return $this->attributeColFactory->create();
    }

    public function getFieldColumns()
    {
        return [];
    }

    /**
     * @param $rowData
     * @return array
     */
    protected function _customFieldsMapping($rowData)
    {
        $headerColumns = $this->_getHeaderColumns();

        foreach ($this->fieldsMap as $systemFieldName => $fileFieldName) {
            if (isset($rowData[$systemFieldName])) {
                $rowData[$fileFieldName] = $rowData[$systemFieldName];
                unset($rowData[$systemFieldName]);
            }
        }
        if (count($headerColumns) != count(array_keys($rowData))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                if (!isset($rowData[$code])) {
                    $newData[$code] = '';
                } else {
                    $newData[$code] = $rowData[$code];
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    protected function isValidAttributeValue($code, $value)
    {
        $isValid = true;
        if (!is_numeric($value) && empty($value)) {
            $isValid = false;
        }

        if (!isset($this->_attributeValues[$code])) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param $category
     * @return array|\Magento\Framework\DataObject[]
     */
    public function getParentCategories($category)
    {
        if ($category->getId() > $this->_storeManager->getStore()->getRootCategoryId()) {
            $path = implode(',', array_reverse($category->getPathIds()));
            $list = $path;
            $categories = array_reverse(explode(',', $list));
            /** @var Collection $categories */
            $collection = $this->entityCollectionFactory->create();
            /*Sort parent categories by level to get correct category path*/
            return $collection
                ->addAttributeToSelect(
                    ['name', 'level']
                )->addFieldToFilter(
                    'entity_id',
                    ['in' => $categories]
                )->setOrder('level', 'ASC')->load()->getItems();
        }

        return [];
    }

    protected function collectMultiselectValues($item, $attrCode)
    {
        $attrValue = $item->getData($attrCode);
        $optionIds = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $attrValue);
        $options = array_intersect_key(
            $this->_attributeValues[$attrCode],
            array_flip($optionIds)
        );
        $str = "";
        foreach ($options as $key => $val) {
            if (strlen($str) > 0) {
                $str .= ",";
            }

            $str .= $key . "=" . $val;
        }

        return $str;
    }
}
