<?php
namespace LR\Matrixtable\Model\Import;
use LR\Matrixtable\Model\Import\ServiceUpgradeImport\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
class ServiceUpgradeImport extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    const ID = 'entity_id';
    const SKU = 'sku';
    const SHIPPINGDAYS = 'shipping_days';
    const SHIPPINGLABLE = 'shipping_lable';
    const SHIPPINGPRICE = 'shipping_price';
    const RECOMMENDED = 'recommended';    
    const TABLE_Entity = 'service_upgrade';
    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
    ValidatorInterface::ERROR_SKU_IS_EMPTY => 'SKU is empty',
    ValidatorInterface::ERROR_SHIPPINGDAYS_IS_EMPTY => 'Shipping Days is empty',
    ValidatorInterface::ERROR_SHIPPINGLABLE_IS_EMPTY => 'Shipping Lable is empty',
    ValidatorInterface::ERROR_SHIPPINGPRICE_IS_EMPTY => 'Shipping Price is empty',
    ValidatorInterface::ERROR_RECOMMENDED_IS_EMPTY => 'Recommended Price is empty',
    ];
     protected $_permanentAttributes = [self::ID];
    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;
    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = [
    self::ID,
    self::SKU,
    self::SHIPPINGDAYS,
    self::SHIPPINGLABLE,
    self::SHIPPINGPRICE,
    self::RECOMMENDED,
    ];
    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;
    protected $_validators = [];
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_connection;
    protected $_resource;
    /**
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     */
    public function __construct(
    \Magento\Framework\Json\Helper\Data $jsonHelper,
    \Magento\ImportExport\Helper\Data $importExportData,
    \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
    \Magento\Framework\App\ResourceConnection $resource,
    \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
    \Magento\Framework\Stdlib\StringUtils $string,
    ProcessingErrorAggregatorInterface $errorAggregator
    ) {
    $this->jsonHelper = $jsonHelper;
    $this->_importExportData = $importExportData;
    $this->_resourceHelper = $resourceHelper;
    $this->_dataSourceModel = $importData;
    $this->_resource = $resource;
    $this->_connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
    $this->errorAggregator = $errorAggregator;
    }
    public function getValidColumnNames()
    {
        return $this->validColumnNames;
    }
    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'service_upgrade';
    }
    /**
     * Row validation.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum)
    {
    $title = false;
    if (isset($this->_validatedRows[$rowNum])) {
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }
    $this->_validatedRows[$rowNum] = true;
    return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }
    /**
     * Create Advanced message data from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     */
    protected function _importData()
    {
        $this->saveEntity();
        return true;
    }
    /**
     * Save Message
     *
     * @return $this
     */
    public function saveEntity()
    {
    $this->saveAndReplaceEntity();
    return $this;
    }
    /**
     * Save and replace data message
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function saveAndReplaceEntity()
    {
    $behavior = $this->getBehavior();
    $listTitle = [];
    while ($bunch = $this->_dataSourceModel->getNextBunch()) {
        $entityList = [];
        foreach ($bunch as $rowNum => $rowData) {
            if (!$this->validateRow($rowData, $rowNum)) {
                $this->addRowError(ValidatorInterface::ERROR_TITLE_IS_EMPTY, $rowNum);
                continue;
            }
            if ($this->getErrorAggregator()->hasToBeTerminated()) {
                $this->getErrorAggregator()->addRowToSkip($rowNum);
                continue;
            }
            $rowTtile= $rowData[self::ID];
            $listTitle[] = $rowTtile;
            $entityList[$rowTtile][] = [
                self::ID => $rowData[self::ID],
                self::SKU => $rowData[self::SKU],
                self::SHIPPINGDAYS => $rowData[self::SHIPPINGDAYS],
                self::SHIPPINGLABLE => $rowData[self::SHIPPINGLABLE],
                self::SHIPPINGPRICE => $rowData[self::SHIPPINGPRICE],
                self::RECOMMENDED => $rowData[self::RECOMMENDED],
            ];
        }
        if (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $behavior) {
            if ($listTitle) {
                if ($this->deleteEntityFinish(array_unique(  $listTitle), self::TABLE_Entity)) {
                    $this->saveEntityFinish($entityList, self::TABLE_Entity);
                }
            }
        } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $behavior) {
            $this->saveEntityFinish($entityList, self::TABLE_Entity);
        }
    }
    return $this;
    }
    /**
     * Save message to customtable.
     *
     * @param array $priceData
     * @param string $table
     * @return $this
     */
    protected function saveEntityFinish(array $entityData, $table)
    {
    if ($entityData) {
        $tableName = $this->_connection->getTableName($table);
        $entityIn = [];
        foreach ($entityData as $id => $entityRows) {
                foreach ($entityRows as $row) {
                    $entityIn[] = $row;
                }
        }
        if ($entityIn) {
            $this->_connection->insertOnDuplicate($tableName, $entityIn,[
                self::ID,
                self::SKU,
                self::SHIPPINGDAYS,
                self::SHIPPINGLABLE,
                self::SHIPPINGPRICE,
                self::RECOMMENDED,
        ]);
        }
    }
    return $this;
    }
}