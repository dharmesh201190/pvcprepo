<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Model\Import\CartPriceRule\RowValidatorInterface as ValidatorInterface;
use Firebear\ImportExport\Model\Source\Type\AbstractType;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;

class CartPriceRule extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    use ImportTrait;

    const TITLE = 'name';

    /**
     * @var array
     */
    protected $cartPriceRuleCache = [];

    /**
     * @var AbstractType
     */
    protected $sourceType;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    protected $repository;

    protected $toDataModel;

    protected $condition;

    /**
     * CartPriceRule constructor.
     *
     * @param JsonHelper $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param LoggerInterface $logger
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\RuleRepository $repository
     * @param \Magento\SalesRule\Model\Converter\ToDataModel $toDataModel
     * @param CartPriceRule\Condition $condition
     */
    public function __construct(
        JsonHelper $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importData,
        ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        LoggerInterface $logger,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\RuleRepository $repository,
        \Magento\SalesRule\Model\Converter\ToDataModel $toDataModel,
        \Firebear\ImportExport\Model\Import\CartPriceRule\Condition $condition
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->_resource = $resource;
        $this->_connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
        $this->_logger = $logger;
        $this->additional = $additional;
        $this->ruleFactory = $ruleFactory;
        $this->repository = $repository;
        $this->toDataModel = $toDataModel;
        $this->condition = $condition;
    }

    /**
     * Create Cart Price Rule entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _importData()
    {
        if (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $this->getBehavior()) {
            $this->saveEntity();
        }
        return true;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        $title = false;
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        if (!isset($rowData[self::TITLE]) || empty($rowData[self::TITLE])) {
            $this->addRowError(ValidatorInterface::ERROR_TITLE_IS_EMPTY, $rowNum);
            return false;
        }
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'cart_price_rule';
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen(\Zend\Serializer\Serializer::serialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                $this->_processedRowsCount++;
                $rowData = $this->customBunchesData($rowData);
                $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                    $startNewBunch = true;
                    $nextRowBackup = [$source->key() => $rowData];
                } else {
                    $bunchRows[$source->key()] = $rowData;
                    $currentDataSize += $rowSize;
                }

                $source->next();
            }
        }

        return $this;
    }

    /**
     * Gather and save information about cart price rule entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function saveEntity()
    {

        $this->_initSourceType('url');

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->cartPriceRuleCache = [];
            foreach ($bunch as $rowNum => $rowData) {
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                if (isset($rowData['conditions'])) {
                    $rowData['conditions'] = stripslashes($rowData['conditions']);
                    $rowData['conditions_serialized'] = $this->condition->parseCondition(
                        $this->jsonHelper->jsonDecode($rowData['conditions'])
                    );
                    if (count($this->condition->arrayAttr) > 0) {
                        foreach ($this->condition->arrayAttr as $attr) {
                            $this->addLogWriteln(__('The attribute %1 is not exist', $attr), $this->output, 'info');
                        }
                    }
                    unset($rowData['conditions']);
                }
                if (isset($rowData['actions'])) {
                    $rowData['actions'] = stripslashes($rowData['actions']);
                    $rowData['actions_serialized'] = $this->condition->parseActionCondition(
                        $this->jsonHelper->jsonDecode($rowData['actions'])
                    );
                    if (count($this->condition->arrayAttr) > 0) {
                        foreach ($this->condition->arrayAttr as $attr) {
                            $this->addLogWriteln(__('The attribute %1 is not exist', $attr), $this->output, 'info');
                        }
                    }
                    unset($rowData['actions']);
                }
                if (isset($rowData['store_labels'])) {
                    $scope = [];
                    $data = explode($this->getMultipleValueSeparator(), $rowData['store_labels']);
                    foreach ($data as $item) {
                        $divide = explode("=", $item);
                        $scope[$divide[0]] = $divide[1];
                    }
                    $rowData['store_labels'] = $scope;
                }
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(
                        __('rule with name: %1 is not valided', $rowData['name']),
                        $this->output,
                        'info'
                    );
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $name = $rowData['name'];
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                foreach ($rowData as $key => $value) {
                    if ($key == 'code') {
                        $rowData['coupon_code'] = $value;
                    }
                }
                if (!empty($rowData)) {
                    try {
                        $rule = $this->ruleFactory->create();
                        $collection = $rule->getCollection();
                        $collection->addFieldToFilter('name', $rowData['name']);
                        if ($collection->getSize() > 0) {
                            $model = $collection->getFirstItem();
                            $data = array_merge($model->getData(), $rowData);
                        } else {
                            $model = $this->ruleFactory->create();
                            $data = $rowData;
                        }
                        $model->setData($data);
                        $model->save();
                    } catch (\Exception $e) {
                        $this->getErrorAggregator()->addError(
                            $e->getCode(),
                            ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                            $this->_processedRowsCount,
                            null,
                            $e->getMessage()
                        );
                        $this->_processedRowsCount++;
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('rule with name: %1 .... %2s', $name, $totalTime), $this->output, 'info');
            }
        }
        return $this;
    }

    protected function _initSourceType($type)
    {
        if (!$this->sourceType) {
            $this->sourceType = $this->additional->getSourceModelByType($type);
            $this->sourceType->setData($this->_parameters);
        }
    }

    public function getMultipleValueSeparator()
    {
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            return $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
    }
}
