<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as MagentoCategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Cms\Model\Page\DomValidationState;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;

class Category extends AbstractEntity
{
    use ImportTrait;

    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    const PSEUDO_MULTI_LINE_SEPARATOR = '|';

    const PAIR_NAME_VALUE_SEPARATOR = '=';

    /**
     * Column category url key.
     */
    const COL_URL = 'url_key';

    const COL_STORE = 'store_view';

    const COL_STORE_NAME = 'store_name';

    /**
     * Column category name.
     */
    const COL_NAME = 'name';

    /**
     * Column category parent id.
     */
    const COL_PARENT = 'parent_id';

    /**
     * Column category path.
     */
    const COL_PATH = 'path';

    /**
     * Column is active.
     */
    const COL_IS_ACTIVE = 'is_active';

    /**
     * Column Include in Menu.
     */
    const COL_INCLUDE_IN_MENU = 'include_in_menu';

    /**
     * Column Custom layout update.
     */
    const COL_CUSTOM_LAYOUT_UPDATE = 'custom_layout_update';

    /**
     * Error codes
     */
    const ERROR_CODE_NAME_REQUIRED = 'columnNameIsRequired';
    const ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID = 'CustomLayoutIsNotValid';

    protected $errorTemplates = [
        self::ERROR_CODE_NAME_REQUIRED => "Column 'name' is not set",
        self::ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID => "Column 'custom_layout_update' is not valid"
    ];

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected $eventManager = null;

    /**
     * Flag for replace operation.
     *
     * @var null
     */
    protected $replaceFlag = null;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $resource;

    protected $resourceFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $categoriesCache = [];

    protected $categoriesUrl;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filterManager;

    /**
     * @var DomValidationState
     */
    private $validationState;

    private $multiLineSeparatorForRegexp;

    protected $attributeCache = [];

    protected $attrData = [];

    protected $attributeCol;

    protected $sourceType;

    protected $nameToId;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    public $productMetadata;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var \Magento\Framework\View\Model\Layout\Update\ValidatorFactory
     */
    protected $validatorFactory;
    /** @var array  */
    protected $notAllowedUpdateWhenEmpty = [self::COL_URL];

    /**
     * @var array
     */
    protected $customAttr = [
        'custom_apply_to_products',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'custom_use_parent_settings',
        'description'
    ];

    /**
     * @param Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param CollectionFactory $categoryColFactory
     * @param CategoryProcessor $categoryProcessor
     * @param CategoryFactory $categoryFactory
     * @param ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ConsoleOutput $output
     * @param \Magento\Framework\Registry $registry
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param DomValidationState $validationState
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $categoryColFactory,
        CategoryProcessor $categoryProcessor,
        CategoryFactory $categoryFactory,
        ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        ConsoleOutput $output,
        Registry $registry,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        $validationState = null
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryProcessor = $categoryProcessor;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->eventManager = $eventManager;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->output = $output;
        $this->attributeCol = $attributeColFactory;
        $this->resourceFactory = $categoryResourceFactory;
        $this->additional = $additional;
        $this->productMetadata = $productMetadata;
        $this->validatorFactory = $validatorFactory;
        $this->validationState = $validationState;

        if (version_compare($this->productMetadata->getVersion(), '2.2.2', '>=') && !$validationState) {
            $this->validationState = ObjectManager::getInstance()->get(
                DomValidationState::class
            );
        }
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );
        $this->_dataSourceModel = $importFireData;
        $this->initCategories()->initAttributes();
        $this->filterManager = $filterManager;
    }

    /**
     * Prepare all existing categories in array
     * @return $this
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $stores = $this->storeManager->getStores();
            $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            foreach ($stores as $store) {
                $this->nameToId[$store->getCode()] = $store->getId();
                $searchStores[] = $store->getId();
            }
            foreach ($searchStores as $store) {
                $collection = $this->categoryColFactory->create();
                $collection->setStoreId($store)
                    ->addAttributeToSelect(self::COL_NAME)
                    ->addAttributeToSelect(self::COL_URL);
                /** @var \Magento\Catalog\Model\Category $category */
                foreach ($collection as $category) {
                    $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                    $pathSize = count($structure);
                    $this->categoriesCache[$category->getId()] = $category;

                    if ($pathSize > 1) {
                        $path = [];
                        for ($i = 1; $i < $pathSize; $i++) {
                            $path[] = $collection->getItemById((int)$structure[$i])->getName();
                        }
                        $index = implode(self::DELIMITER_CATEGORY, $path);
                        $this->categories[$index] = $category->getId();
                    } else {
                        $this->categories[$category->getName()] = $category->getId();
                    }
                }
            }
        }

        $this->setupKeyUrls();

        return $this;
    }

    protected function initAttributes()
    {
        foreach ($this->attributeCol->create() as $item) {
            $this->attrData[$item->getAttributeCOde()] = $item->getData();
        }
    }

    protected function searchInCategories($id, $data)
    {
        $array = [];
        foreach ($data as $el) {
            if ($el['entity_id'] == $id) {
                $array[$el['value']];
            }
        }

        return $array;
    }

    /**
     * Create Category entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _importData()
    {
        /**
         * Add templates here because we rewrite aggregator in General Trait
         */
        foreach ($this->errorTemplates as $errorCode => $message) {
            $this->errorAggregator
                ->addErrorMessageTemplate($errorCode, $message);
        }

        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deleteCategories();
        } else {
            /**
             * If user select replace behavior all categories will be deleted first,
             * then new categories will be saved
             */
            $this->saveCategoriesData();
        }
        $this->eventManager->dispatch('catalog_category_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Delete categories is delete behavior is selected
     * @return $this
     */
    protected function deleteCategories()
    {
        $categoryId = null;
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                if (isset($rowData['name']) && isset($this->categories[$rowData['name']])) {
                    $categoryId = (int)$this->categories[$rowData['name']];
                } elseif (isset($rowData['entity_id'])) {
                    $categoryId = (int)$rowData['entity_id'];
                }

                if ($categoryId) {
                    if ($this->categoryFactory->create()->
                    getCollection()->addFieldToFilter('entity_id', $categoryId)
                        ->getSize()) {
                        try {
                            $category = $this->categoryRepository->get($categoryId);
                            if ($this->getResource()->isForbiddenToDelete($categoryId)) {
                                $this->addRowError(
                                    'Cannot delete category ',
                                    $rowNum
                                );
                            } else {
                                $this->categoryRepository->delete($category);
                            }
                        } catch (\Magento\Framework\Exception\StateException $e) {
                            $this->addRowError(
                                $e->getMessage(),
                                $rowNum
                            );
                        }
                    }
                } else {
                    $this->addRowError(
                        'Cannot delete category ',
                        $rowNum
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Delete all categories when replace behavior is selected
     * @return $this
     */
    protected function deleteAllCategories()
    {
        $this->deleteCategories();

        /**
         * Clear categories cache.
         */
        $this->categories = [];
        $this->categoriesCache = [];

        /**
         * Re-init default categories.
         */
        $this->initCategories();

        return $this;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function saveCategoriesData()
    {
        /**
         * Delete all categories if replace behavior is selected
         */
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->deleteAllCategories();
        }
        $this->_initSourceType('url');

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $in = 0;
            $up = 0;
            $this->categoriesCache = [];
            $bunch = $this->prepareImagesFromSource($bunch);
            foreach ($bunch as $rowNum => $rowData) {
                if ($rowData['name'] == 'Root Catalog') {
                    continue;
                }
                $this->_processedRowsCount++;
                $rowData = $this->joinIdenticalyData($rowData);
                $rowData = $this->customChangeData($rowData);
                $rowData = $this->clearEmptyData($rowData, $rowNum);

                if (!$rowData) {
                    continue;
                }

                if (!isset($rowData[self::COL_NAME])) {
                    $this->getErrorAggregator()->addError(
                        self::ERROR_CODE_NAME_REQUIRED,
                        ProcessingError::ERROR_LEVEL_CRITICAL,
                        $this->_processedRowsCount
                    );
                    continue;
                }

                if (isset($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])
                    && !empty($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
                    $rowData[self::COL_CUSTOM_LAYOUT_UPDATE] = stripslashes($rowData[self::COL_CUSTOM_LAYOUT_UPDATE]);
                    if (!$this->validateLayoutUpdateRow($rowData[self::COL_CUSTOM_LAYOUT_UPDATE])) {
                        $this->getErrorAggregator()->addError(
                            self::ERROR_CODE_LAYOUT_UPDATE_IS_NOT_VALID,
                            ProcessingError::ERROR_LEVEL_WARNING,
                            $this->_processedRowsCount
                        );
                        continue;
                    }
                }

                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(
                        __('Category with name: %1 is not valided', $rowData[self::COL_NAME]),
                        $this->output,
                        'info'
                    );
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $name = $rowData[self::COL_NAME];
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    if (isset($rowData['entity_id'])) {
                        unset($rowData['entity_id']);
                    }
                }

                $rowData = $this->checkUrl($rowData);
                $rowData = $this->changeData($rowData);
                $rowData['store_id'] = 0;
                if (!empty($rowData[self::COL_STORE])) {
                    if (isset($this->nameToId[$rowData[self::COL_STORE]])) {
                        $rowData['store_id'] = $this->nameToId[$rowData[self::COL_STORE]];
                        unset($rowData[self::COL_STORE]);
                    }
                }
                $rowPath = null;
                $rowPath = str_replace(
                    $this->_parameters['category_levels_separator'],
                    self::DELIMITER_CATEGORY,
                    $rowData[self::COL_NAME]
                );

                if (!empty($rowPath)) {
                    if (is_int($rowPath)) {
                        try {
                            /** @var \Magento\Catalog\Model\Category $category */
                            $category = $this->categoryFactory->create();
                            if (!($parentCategory = isset($this->categoriesCache[$rowPath])
                                ? $this->categoriesCache[$rowPath] : null)
                            ) {
                                $parentCategory = $this->categoryFactory->create()->load($rowPath);
                            }
                            $category->setParentId($rowPath);
                            $category->setIsActive(
                                isset($rowData[self::COL_IS_ACTIVE]) ? $rowData[self::COL_IS_ACTIVE] : true
                            );
                            $category->setIncludeInMenu(
                                isset($rowData[self::COL_INCLUDE_IN_MENU]) ? $rowData[self::COL_INCLUDE_IN_MENU] : true
                            );
                            $category->setAttributeSetId($category->getDefaultAttributeSetId());
                            $category->setStoreId($rowData['store_id']);
                            $category->addData($rowData);

                            $category->setPath($parentCategory->getPath());
                            $category->save();
                            $this->categoriesCache[$category->getId()] = $category;
                            $in++;
                        } catch (\Exception $e) {
                            $this->getErrorAggregator()->addError(
                                $e->getCode(),
                                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                                $this->_processedRowsCount,
                                null,
                                $e->getMessage()
                            );
                        }
                    } else {
                        if (!isset($this->categories[$rowPath])) {
                            ++$in;
                            $result = $this->prepareCategoriesByPath($rowPath, $rowData);
                        } else {
                            ++$up;
                            $result = $this->updateCategoriesByPath($rowPath, $rowData);
                        }
                        if ($result === false) {
                            continue;
                        }
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('category with name: %1 .... %2s', $name, $totalTime), $this->output, 'info');
            }
            $this->addLogWriteln(__('Imported: %1 rows', $in), $this->output, 'info');
            $this->addLogWriteln(__('Updated: %1 rows', $up), $this->output, 'info');

            $this->eventManager->dispatch(
                'catalog_category_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * @param $rowData
     *
     * @return array
     */
    protected function clearEmptyData($rowData, $rownum)
    {
        foreach ($this->attrData as $attrDatum) {
            if (isset($rowData[$attrDatum['attribute_code']]) && $rowData[$attrDatum['attribute_code']] == '') {
                if ($attrDatum['is_required'] &&
                    !in_array($attrDatum['attribute_code'], ['available_sort_by','default_sort_by'])) {
                    $message = __('A required attribute missing %1', $attrDatum['attribute_code']);
                    $this->getErrorAggregator()->addError(
                        self::ERROR_CODE_NAME_REQUIRED,
                        ProcessingError::ERROR_LEVEL_CRITICAL,
                        $rownum,
                        $attrDatum['attribute_code'],
                        $message,
                        $message
                    );
                    return [];
                } else {
                    unset($rowData[$attrDatum['attribute_code']]);
                }
            }
        }
        return $rowData;
    }

    /**
     * Prepare new category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function prepareCategoriesByPath($rowPath, $rowData)
    {
        $result = true;
        $parentId = MagentoCategoryModel::TREE_ROOT_ID;
        $pathParts = explode($this->_parameters['category_levels_separator'], $rowPath);
        $path = '';
        foreach ($pathParts as $pathPart) {
            if ($pathPart == '') {
                continue;
            }
            $path .= $pathPart;
            if (!isset($this->categories[$path])) {
                try {
                    $category = $this->categoryFactory->create();
                    if (!($parentCategory = isset($this->categoriesCache[$parentId])
                        ? $this->categoriesCache[$parentId] : null)
                    ) {
                        $parentCategory = $this->categoryFactory->create()->load($parentId);
                    }
                    $category->addData($rowData);
                    $category->setStoreId(0);
                    $category->setParentId($parentId);
                    $category->setIsActive(isset($rowData[self::COL_IS_ACTIVE]) ? $rowData[self::COL_IS_ACTIVE] : true);
                    $category->setIncludeInMenu(
                        isset($rowData[self::COL_INCLUDE_IN_MENU]) ? $rowData[self::COL_INCLUDE_IN_MENU] : true
                    );
                    $category->setAttributeSetId($category->getDefaultAttributeSetId());
                    $category->setName($pathPart);
                    if (isset($rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY])
                        && !empty($rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY])
                    ) {
                        $attrValue = \explode(
                            $this->getMultipleValueSeparator(),
                            $rowData[MagentoCategoryModel::KEY_AVAILABLE_SORT_BY]
                        );
                        $category->setAvailableSortBy($attrValue);
                    }
                    $category->setPath($parentCategory->getPath());
                    $category->save();
                    if ($category->getId()) {
                        $category->setPath($parentCategory->getPath() . self::DELIMITER_CATEGORY . $category->getId());
                        $category->save();
                    }
                    $this->categoriesCache[$category->getId()] = $category;
                    $this->categories[$path] = $category->getId();
                    if (!empty($rowData[self::COL_STORE_NAME])) {
                        $this->updateCategoriesByPath($rowPath, $rowData);
                    }
                } catch (\Exception $e) {
                    $this->getErrorAggregator()->addError(
                        $e->getCode(),
                        ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                        $this->_processedRowsCount,
                        null,
                        $e->getMessage()
                    );
                    $result = false;
                }
            }
            if (isset($this->categories[$path])) {
                $parentId = $this->categories[$path];

                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $result;
    }

    /**
     * Update existing category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function updateCategoriesByPath($rowPath, $rowData)
    {
        $result = true;
        $categoryId = $this->categories[$rowPath];
        $category = $this->categoryFactory->create()->setStoreId($rowData['store_id'])->load($categoryId);
        /**
         * Avoid changing category name and path
         */
        if (isset($rowData[self::COL_STORE_NAME]) && !empty($rowData[self::COL_STORE_NAME])) {
            $rowData[self::COL_NAME] = $rowData[self::COL_STORE_NAME];
            unset($rowData[self::COL_STORE_NAME]);
        } elseif (isset($rowData[self::COL_NAME])) {
            unset($rowData[self::COL_NAME]);
        }

        if (isset($rowData[self::COL_PATH])) {
            unset($rowData[self::COL_PATH]);
        }
        try {
            foreach (\array_keys($this->attrData) as $attrCode) {
                if (!isset($rowData[$attrCode])
                    || \in_array($attrCode, $this->notAllowedUpdateWhenEmpty, false)
                ) {
                    continue;
                }
                if (!empty($rowData[$attrCode])
                 && $category->getData($attrCode) !== $rowData[$attrCode]
                ) {
                    if ($attrCode === MagentoCategoryModel::KEY_AVAILABLE_SORT_BY) {
                        $attrValue = \explode(
                            $this->getMultipleValueSeparator(),
                            $rowData[$attrCode]
                        );
                        $category->setData($attrCode, $attrValue);
                    } else {
                        $category->setData($attrCode, $rowData[$attrCode]);
                    }
                }
            }

            $category->setStoreId($rowData['store_id']);
            $category->save();
        } catch (\Exception $e) {
            $this->getErrorAggregator()->addError(
                $e->getCode(),
                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                $this->_processedRowsCount,
                null,
                $e->getMessage()
            );
            $result = false;
        }

        return $result;
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
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Validate custom update row.
     *
     * @param string $validateString
     * @return boolean
     */
    protected function validateLayoutUpdateRow($validateString)
    {
        $layoutXmlValidator = $this->validatorFactory->create(
            [
                'validationState' => $this->validationState,
            ]
        );
        try {
            return $layoutXmlValidator->isValid($validateString);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    protected function setupKeyUrls()
    {
        $this->categoriesUrl = [];
        $collection = $this->categoryColFactory->create();
        $collection->addAttributeToSelect(self::COL_URL);
        foreach ($collection as $category) {
            $this->categoriesUrl[] = $category[self::COL_URL];
        }
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function checkUrl($rowData)
    {
        if (isset($rowData[self::COL_URL])) {
            $url = $this->searchUrl($rowData[self::COL_URL]);
            $rowData[self::COL_URL] = $url;
            $this->categoriesUrl[] = $url;
        }

        return $rowData;
    }

    /**
     * @param $url
     * @return mixed
     */
    protected function searchUrl($url)
    {
        if (in_array($url, $this->categoriesUrl)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            } else {
                $url = $url;
            }
        }

        if ($this->checkUrlKeyDuplicates($url)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            }
        }
        return $url;
    }

    /**
     * @return array
     */
    protected function getDataAttributes()
    {
        $category = $this->categoryFactory->create()->getResource();
        $attr = $category->getAttribute(self::COL_NAME);
        $attrName = $attr->getId();
        $table = $attr->getBackendTable();
        $entityTable = $category->getEntityTable();
        $collection = $this->categoryColFactory->create();
        $connection = $collection->getConnection();
        $indexList = $connection->getIndexList($entityTable);
        $entityIdField = $indexList[$connection->getPrimaryKeyName($entityTable)]['COLUMNS_LIST'][0];
        $stores = $this->storeManager->getStores();
        $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        foreach ($stores as $store) {
            $searchStores[] = $store->getId();
        }
        $select = $connection->select()->from(
            ['t_d' => $table],
            [$entityIdField, 'value']
        )
            ->where(
                't_d.attribute_id=?',
                $attrName
            )
            ->where(
                't_d.store_id IN(?)',
                $searchStores
            )
            ->where(
                't_d.store_id = ?',
                $connection->getIfNullSql('t_d.store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            );

        return $this->_connection->fetchAll($select);
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
                $rowData = $this->customFieldsMapping($rowData);
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

    protected function parseAdditionalAttributes($attributes)
    {
        return empty($this->_parameters[Import::FIELDS_ENCLOSURE])
            ? $this->parseAttributesWithoutWrappedValues($attributes)
            : $this->parseAttributesWithWrappedValues($attributes);
    }

    private function parseAttributesWithoutWrappedValues($data)
    {
        $attributeNameValuePairs = explode(
            $this->getMultipleValueSeparator(),
            $data
        );
        $result = [];
        $code = '';
        foreach ($attributeNameValuePairs as $attributeData) {
            //process case when attribute has ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR inside its value
            if (strpos($attributeData, self::PAIR_NAME_VALUE_SEPARATOR) === false) {
                if (!$code) {
                    continue;
                }
                $result[$code] .= $this->getMultipleValueSeparator() . $attributeData;
                continue;
            }
            list($code, $value) = explode(
                self::PAIR_NAME_VALUE_SEPARATOR,
                $attributeData,
                2
            );
            $code = mb_strtolower($code);
            $result[$code] = $value;
        }
        return $result;
    }

    private function parseAttributesWithWrappedValues($data)
    {
        $attributesArray = [];
        preg_match_all(
            '~((?:[a-zA-Z0-9_])+)="((?:[^"]|""|"'
            . $this->getMultiLineSeparatorForRegexp()
            . '")+)"+~',
            $data,
            $matches
        );
        foreach ($matches[1] as $i => $attributeCode) {
            $attribute = $this
                ->retrieveAttributeByCode($attributeCode);
            $value = 'multiselect' != $attribute->getFrontendInput()
                ? str_replace('""', '"', $matches[2][$i])
                : '"' . $matches[2][$i] . '"';
            $attributesArray[mb_strtolower($attributeCode)] = $value;
        }
        return $attributesArray;
    }

    public function getMultipleValueSeparator()
    {
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            return $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
    }

    private function getMultiLineSeparatorForRegexp()
    {
        if (!$this->multiLineSeparatorForRegexp) {
            $this->multiLineSeparatorForRegexp = in_array(self::PSEUDO_MULTI_LINE_SEPARATOR, str_split('[\^$.|?*+(){}'))
                ? '\\' . self::PSEUDO_MULTI_LINE_SEPARATOR
                : self::PSEUDO_MULTI_LINE_SEPARATOR;
        }
        return $this->multiLineSeparatorForRegexp;
    }

    /**
     * @param $rowData
     *
     * @return mixed
     */
    public function customFieldsMapping($rowData)
    {
        if (isset($rowData[self::COL_NAME])) {
            $actualName = \explode(self::DELIMITER_CATEGORY, $rowData[self::COL_NAME]);
            $rowData['_actual_name'] = \end($actualName);
        }
        if ($this->_parameters['generate_url'] && isset($rowData[self::COL_NAME])) {
            $rowData = $this->generateUrlKey($rowData);
        }

        return $rowData;
    }

    /**
     * @param $rowData
     *
     * @return mixed
     */
    private function generateUrlKey($rowData)
    {
        $urlKey = isset($rowData[self::COL_URL])
            ? $this->formatUrlKey($rowData[self::COL_URL])
            : $this->formatUrlKey($rowData['_actual_name']);
        if ($this->checkUrlKeyDuplicates($urlKey) && !isset($this->categoriesUrl[$urlKey])) {
            $this->categoriesUrl[] = $urlKey = $this->formatUrlKey(
                $rowData['_actual_name']
            );
        }
        $rowData[self::COL_URL] = $urlKey;
        return $rowData;
    }

    public function retrieveAttributeByCode($attrCode)
    {
        /** @var string $attrCode */
        $attrCode = mb_strtolower($attrCode);

        if (!isset($this->attributeCache[$attrCode])) {
            $this->attributeCache[$attrCode] = $this->getResource()->getAttribute($attrCode);
        }

        return $this->attributeCache[$attrCode];
    }

    public function changeData($rowData)
    {
        foreach ($this->customAttr as $value) {
            if (!array_key_exists($value, $rowData)) {
                continue;
            }
            $rowData[$value] = stripslashes($rowData[$value]);
        }

        foreach ($rowData as $key => $value) {
            if (isset($this->attrData[$key])) {
                $h = $this->attrData[$key];
                if ($h['frontend_input'] == 'select') {
                    $data = $this->retrieveAttributeByCode($key);
                    if (!empty($data->getOptions())) {
                        foreach ($data->getOptions() as $valueOptions) {
                            $valueData = $valueOptions->getData();
                            if ($valueData['label'] == $value) {
                                $rowData[$key] = $valueData['value'];
                            }
                        }
                    }
                }
            }
        }

        return $rowData;
    }

    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceFactory->create();
        }
        return $this->resource;
    }

    protected function checkUrlKeyDuplicates($urlKeys)
    {
        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $resource->getTable('catalog_product_entity')],
            'cpe.entity_id = url_rewrite.entity_id'
        )->where('request_path LIKE "%' . $urlKeys . '"')
            ->orWhere('request_path LIKE "%' . $urlKeys . '.html"');
        $urlKeyDuplicates = $this->_connection->fetchAssoc(
            $select
        );

        return count($urlKeyDuplicates);
    }

    protected function prepareImagesFromSource($bunch)
    {
        $image = 'image';

        foreach ($bunch as $rowNum => &$rowData) {
            if (empty($rowData[$image])) {
                continue;
            }
            $importImage = $rowData[$image];
            $imageSting = mb_strtolower(preg_replace('/[^a-z0-9\._-]+/i', '', $importImage));
            if ($this->sourceType) {
                $this->sourceType->importImageCategory($importImage, $imageSting);
            }
            $imageArr = $imageSting;

            $rowData[$image] = $imageArr;
        }

        return $bunch;
    }

    protected function _initSourceType($type)
    {
        if (!$this->sourceType) {
            $this->sourceType = $this->additional->getSourceModelByType($type);
            $this->sourceType->setData($this->_parameters);
        }
    }

    /**
     * Returns initial Categories set in initCategories() method
     *
     * @return array
     */
    public function getInitialCategories()
    {
        return $this->categories;
    }

    /**
     * Format URL key from name or defined key
     *
     * @param string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->filterManager->translitUrl($str);
    }
}
