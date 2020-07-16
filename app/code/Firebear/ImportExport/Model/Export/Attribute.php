<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;
use Firebear\ImportExport\Model\Export\Dependencies\Config as ExportConfig;
use Firebear\ImportExport\Model\Source\Factory as SourceFactory;
use Firebear\ImportExport\Model\ExportJob\Processor;
use Firebear\ImportExport\Traits\Export\Entity as ExportTrait;
use Firebear\ImportExport\Helper\Data as Helper;
use Firebear\ImportExport\Model\Import\Attribute as ImportAttribute;

/**
 * Attribute export adapter
 */
class Attribute extends AbstractEntity implements EntityInterface
{
    use ExportTrait;

    /**
     * Attribute collection name
     */
    const ATTRIBUTE_COLLECTION_NAME = AttributeCollection::class;

    /**
     * XML path to page size parameter
     */
    const XML_PATH_PAGE_SIZE = 'firebear_importexport/page_size/attribute';

    /**
     * Export config data
     *
     * @var array
     */
    protected $_exportConfig;

    /**
     * Source Factory
     *
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $_sourceFactory;

    /**
     * Helper
     *
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $_helper;

    /**
     * Resource Model
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceModel;

    /**
     * DB connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Item export data
     *
     * @var array
     */
    protected $_exportData = [];

    /**
     * EAV config
     *
     * @var array
     */
    protected $_eavConfig;

    /**
     * Catalog product entity typeId
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * Initialize export
     *
     * @param LoggerInterface $logger
     * @param ConsoleOutput $output
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ExportFactory $collectionFactory
     * @param CollectionByPagesIteratorFactory $resourceColFactory
     * @param ExportConfig $exportConfig
     * @param SourceFactory $sourceFactory
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        ConsoleOutput $output,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        ExportConfig $exportConfig,
        SourceFactory $sourceFactory,
        ResourceConnection $resource,
        Helper $helper,
        EavConfig $eavConfig,
        array $data = []
    ) {
        $this->_logger = $logger;
        $this->output = $output;
        $this->_exportConfig = $exportConfig->get();
        $this->_sourceFactory = $sourceFactory;
        $this->_resourceModel = $resource;
        $this->_connection = $resource->getConnection();
        $this->_helper = $helper;
        $this->_eavConfig = $eavConfig;

        parent::__construct(
            $scopeConfig,
            $storeManager,
            $collectionFactory,
            $resourceColFactory,
            $data
        );

        $this->_initStores();
    }

    /**
     * Retrieve entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'attribute';
    }

    /**
     * Retrieve header columns
     *
     * @return array
     */
    public function _getHeaderColumns()
    {
        return $this->changeHeaders(
            array_keys($this->describeTable())
        );
    }

    /**
     * Retrieve attribute collection
     *
     * @return \Magento\Eav\Model\ResourceModel\Attribute\Collection
     */
    protected function _getEntityCollection()
    {
        return $this->getAttributeCollection()->setEntityTypeFilter(
            $this->_getEntityTypeId()
        );
    }
    /**
     * Get entity type id
     *
     * @return int
     */
    protected function _getEntityTypeId()
    {
        if (!$this->_entityTypeId) {
            $entityType = $this->_eavConfig->getEntityType('catalog_product');
            $this->_entityTypeId = $entityType->getId();
        }
        return $this->_entityTypeId;
    }

    /**
     * Export process
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $this->addLogWriteln(__('Begin Export'), $this->output);
        $this->addLogWriteln(__('Scope Data'), $this->output);

        $collection = $this->_getEntityCollection();
        $this->_prepareEntityCollection($collection);
        $this->_exportCollectionByPages($collection);
        // create export file
        return [
            $this->getWriter()->getContents(),
            $this->_processedEntitiesCount,
            $this->lastEntityId
        ];
    }

    /**
     * Export one item
     *
     * @param \Magento\Framework\Model\AbstractModel $item
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportItem($item)
    {
        foreach ($this->_getExportData($item) as $storeRow) {
            foreach ($storeRow as $row) {
                $row = $this->changeRow($row);
                $this->getWriter()->writeRow($row);
            }
        }
        $this->_processedEntitiesCount++;
    }

    /**
     * Get export data for collection
     *
     * @param \Magento\Framework\Model\AbstractModel $attribute
     * @return array
     */
    protected function _getExportData($attribute)
    {
        $this->_exportData = [];
        $attributeId = $attribute->getId();
        $code = $attribute->getAttributeCode();
        $this->lastEntityId = $attributeId;

        $setData = $this->_getSetData($attributeId) ?: null;
        $row = [
            'store_id' => 0,
            'entity_type' => 'product',
            'attribute_set' => $setData['attribute_set_name'] ?? null,
            'group:name' => $setData['attribute_group_name'] ?? null,
            'group:sort_order' => $setData['sort_order'] ?? null
        ];
        $row = array_merge($row, $attribute->toArray());
        unset(
            $row['attribute_id'],
            $row['entity_type_id']
        );
        $row['option:base_value'] = '';
        $row['option:value'] = '';
        $row['option:sort_order'] = '';
        $row['option:swatch_value'] = '';

        $exportData = [0 => $row];
        $pattern = array_fill_keys(array_keys($row), '');
        unset($row);

        $labels = $this->_getStoreLabels($attributeId);
        foreach ($labels as $storeId => $label) {
            $new = $pattern;
            $new['attribute_set'] = $setData['attribute_set_name'] ?? null;
            $new['attribute_code'] = $code;
            $new['store_id'] = $storeId;
            $new['frontend_label'] = $label;
            $exportData[$storeId] = $new;
        }

        ksort($exportData);
        $baseValue = [];
        foreach ($exportData as $exportStoreId => $row) {
            foreach ($this->_storeIdToCode as $storeId => $storeCode) {
                if ($exportStoreId != $storeId && isset($exportData[$storeId])) {
                    continue;
                }
                $optionSwatch = null;
                if ($row['additional_data'] != null) {
                    $optionSwatch = $this->getOptionDataSwatches($attributeId, $storeId);
                }
                $options = $this->_getOptionData($attributeId, $storeId);
                if (0 < count($options)) {
                    $first = true;
                    $count = 0;
                    foreach ($options as $option) {
                        $new = $first ? $row : $pattern;
                        $first = false;

                        $optionId = (int)$option['option_id'];

                        if (!isset($exportData[$storeId])) {
                            $new = $pattern;
                        }

                        if (0 == $storeId) {
                            $baseValue[$optionId] = $option['value'];
                        }
                        $new['attribute_set'] = $setData['attribute_set_name'] ?? null;
                        $new['attribute_code'] = $code;
                        $new['store_id'] = $storeId;
                        $new['option:base_value'] = ($storeId && isset($baseValue[$optionId])) ?
                            $baseValue[$optionId] :
                            '';
                        $new['option:value'] = $option['value'];
                        $new['option:swatch_value'] = !empty($optionSwatch[$count]['value']) ?
                            $optionSwatch[$count]['value'] :
                            null;
                        $new['option:sort_order'] = $option['sort_order'];

                        $this->_exportData[$storeId][$optionId] = $new;
                        $count++;
                    }
                } elseif ($exportStoreId == $storeId) {
                    $this->_exportData[$storeId][0] = $row;
                }
            }
        }
        return $this->_exportData;
    }

    /**
     * Get set data for attribute
     *
     * @param integer $attributeId
     * @return array
     */
    protected function _getSetData($attributeId)
    {
        $resource = $this->_resourceModel;
        $table = $resource->getTableName('eav_entity_attribute');
        $setTable = $resource->getTableName('eav_attribute_set');
        $groupTable = $resource->getTableName('eav_attribute_group');

        $select = $this->_connection->select();
        $select->from(
            ['e' => $table],
            []
        )->join(
            ['s' => $setTable],
            'e.attribute_set_id = s.attribute_set_id',
            ['s.attribute_set_name']
        )->join(
            ['g' => $groupTable],
            'e.attribute_group_id = g.attribute_group_id',
            ['g.attribute_group_name', 'g.attribute_group_code', 'g.tab_group_code', 'g.sort_order']
        )->where(
            'e.attribute_id = ?',
            $attributeId
        );
        return $this->_connection->fetchRow($select);
    }

    /**
     * Get option data for attribute
     *
     * @param integer $attributeId
     * @param integer $storeId
     * @return array
     */
    protected function _getOptionData($attributeId, $storeId)
    {
        $resource = $this->_resourceModel;
        $optionTable = $resource->getTableName('eav_attribute_option');
        $optionValueTable = $resource->getTableName('eav_attribute_option_value');

        $select = $this->_connection->select();
        $select->from(
            ['o' => $optionTable],
            ['o.option_id', 'o.sort_order']
        )->join(
            ['v' => $optionValueTable],
            'o.option_id = v.option_id',
            ['v.value']
        )->where(
            'o.attribute_id = ?',
            $attributeId
        )->where(
            'v.store_id = ?',
            $storeId
        )->order('o.sort_order');

        return $this->_connection->fetchAll($select);
    }

    /**
     * Returns Select for attributes Ids.
     *
     * Builds a "Select" object which loads all EAV attributes that has "swatch" options
     *
     * @return array
     */
    private function getOptionDataSwatches($attributeId, $storeId)
    {
        $resource = $this->_resourceModel;
        $optionTable = $resource->getTableName('eav_attribute_option');
        $optionValueTable = $resource->getTableName('eav_attribute_option_swatch');
        $select = $this->_connection->select();
        $select->from(
            ['o' => $optionTable],
            ['o.option_id', 'o.sort_order']
        )->join(
            ['v' => $optionValueTable],
            'o.option_id = v.option_id',
            ['v.value']
        )->where(
            'o.attribute_id = ?',
            $attributeId
        )->where(
            'v.store_id = ?',
            $storeId
        )->order('o.sort_order');

        return $this->_connection->fetchAll($select);
    }

    /**
     * Checks if nested structure
     *
     * @return bool
     */
    protected function _isNested()
    {
        return in_array(
            $this->_parameters['behavior_data']['file_format'],
            ['xml', 'json']
        );
    }

    /**
     * Apply filter to collection
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    protected function _prepareEntityCollection(AbstractCollection $collection)
    {
        if (!empty($this->_parameters['last_entity_id']) &&
            $this->_parameters['enable_last_entity_id'] > 0
        ) {
            $collection->addFieldToFilter(
                'main_table.attribute_id',
                ['gt' => $this->_parameters['last_entity_id']]
            );
        }
        $collection->addFieldToFilter('additional_table.is_visible', 1);

        if (!isset($this->_parameters[Processor::EXPORT_FILTER_TABLE]) ||
            !is_array($this->_parameters[Processor::EXPORT_FILTER_TABLE])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[Processor::EXPORT_FILTER_TABLE];
        }

        $filters = [];
        $entity = $this->getEntityTypeCode();
        foreach ($exportFilter as $data) {
            if ($data['entity'] == $entity) {
                $filters[$data['field']] = $data['value'];
            }
        }

        $fields = [];
        $columns = $this->getFieldColumns();
        foreach ($columns['attribute'] as $field) {
            $fields[$field['field']] = $field['type'];
        }

        foreach ($filters as $key => $value) {
            if (isset($fields[$key])) {
                $type = $fields[$key];
                if ('text' == $type) {
                    $value = $value;
                    if (is_scalar($value)) {
                        trim($value);
                    }
                    $collection->addFieldToFilter($key, ['like' => "%{$value}%"]);
                } elseif ('select' == $type) {
                    $collection->addFieldToFilter($key, ['eq' => $value]);
                } elseif ('int' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($value);
                        $to = array_shift($value);

                        if (is_numeric($from)) {
                            $collection->addFieldToFilter($key, ['from' => $from]);
                        }
                        if (is_numeric($to)) {
                            $collection->addFieldToFilter($key, ['to' => $to]);
                        }
                    }
                } elseif ('date' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($exportFilter[$value]);
                        $to = array_shift($exportFilter[$value]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new \DateTime($from))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new \DateTime($to))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['to' => $date, 'date' => true]);
                        }
                    }
                }
            }
        }
        return $collection;
    }

    /**
     * Retrieve store labels by given attribute id
     *
     * @param int $attributeId
     * @return array
     */
    protected function _getStoreLabels($attributeId)
    {
        return $this->getAttributeCollection()->getResource()
            ->getStoreLabelsByAttributeId($attributeId);
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFieldsForExport()
    {
        $fields = array_keys($this->describeTable());
        $fields = array_merge(ImportAttribute::getAdditionalColumns(), $fields);
        return $fields;
    }

    /**
     * Retrieve entity field columns
     *
     * @return array
     */
    public function getFieldColumns()
    {
        $options = [];
        foreach ($this->describeTable() as $key => $field) {
            if ($field == 'entity_type_id' || $field == 'is_visible') {
                continue;
            }
            $select = [];
            $type = $this->_helper->convertTypesTables($field['DATA_TYPE']);
            if ('int' == $type && (
                'is_' == substr($field['COLUMN_NAME'], 0, 3) ||
                'used_' == substr($field['COLUMN_NAME'], 0, 5)
            )) {
                $select[] = ['label' => __('Yes'), 'value' => 1];
                $select[] = ['label' => __('No'), 'value' => 0];
                $type = 'select';
            }
            $options['attribute'][] = ['field' => $key, 'type' => $type, 'select' => $select];
        }
        $options['attribute'][] = ['field' => 'store_id', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'attribute_set', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'group:name', 'type' => 'text', 'select' => []];
        $options['attribute'][] = ['field' => 'group:sort_order', 'type' => 'text', 'select' => []];
        return $options;
    }

    /**
     * Retrieve entity field for filter
     *
     * @return array
     */
    public function getFieldsForFilter()
    {
        $options = [];
        foreach ($this->getFieldsForExport() as $field) {
            $options[] = [
                'label' => $field,
                'value' => $field
            ];
        }
        return [$this->getEntityTypeCode() => $options];
    }

    /**
     * Retrieve the column descriptions for a table, include additional table
     *
     * @return array
     */
    protected function describeTable()
    {
        $resource = $this->getAttributeCollection()->getResource();
        $additionalTable = $resource->getAdditionalAttributeTable(
            $this->_getEntityTypeId()
        );
        $fields = $resource->describeTable($resource->getMainTable());
        $fields+= $resource->describeTable($this->_resourceModel->getTableName($additionalTable));

        unset($fields['attribute_id']);
        return $fields;
    }
}
