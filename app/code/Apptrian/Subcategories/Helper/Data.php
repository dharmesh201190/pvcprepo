<?php
/**
 * @category  Apptrian
 * @package   Apptrian_Subcategories
 * @author    Apptrian
 * @copyright Copyright (c) 2017 Apptrian (http://www.apptrian.com)
 * @license   http://www.apptrian.com/license Proprietary Software License EULA
 */
 
namespace Apptrian\Subcategories\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    public $moduleList;
    
    /**
     * @var \Magento\Framework\Escaper
     */
    public $escaper;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    public $viewAssetRepo;
    
    /**
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry;
    
    /**
     * @var \Magento\Catalog\Helper\Output
     */
    public $catalogHelperOutput;
    
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    
    /**
     * @var array|null
     */
    public $options = null;
    
    /**
     * @var array
     */
    public $elements = [
        'image'       => '0',
        'name'        => '1',
        'description' => '2'
    ];
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\Asset\Repository $viewAssetRepo
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Helper\Output $catalogHelperOutput
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Escaper $escaper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $viewAssetRepo,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Helper\Output $catalogHelperOutput,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        
        $this->scopeConfig         = $context->getScopeConfig();
        $this->moduleList          = $moduleList;
        $this->escaper             = $escaper;
        $this->storeManager        = $storeManager;
        $this->viewAssetRepo       = $viewAssetRepo;
        $this->coreRegistry        = $coreRegistry;
        $this->catalogHelperOutput = $catalogHelperOutput;
        $this->categoryFactory     = $categoryFactory;
        
        parent::__construct($context);
    }
    
    /**
     * Returns extension version.
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        $moduleCode = 'Apptrian_Subcategories';
        $moduleInfo = $this->moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];
    }
    
    /**
     * Based on provided configuration path returns configuration value.
     *
     * @param string $configPath
     * @return string
     */
    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Decodes provided options from "json-like" string to array.
     *
     * @param string $value
     * @return array|null
     */
    public function decode($value)
    {
        return json_decode(
            str_replace(['[', ']', '`', '|'], ['{', '}', '"', '\\'], $value),
            true
        );
    }
    
    /**
     * Based on provided $sortOrderString returns array of sorted elements.
     *
     * @param string $sortOrderString
     * @return array
     */
    public function getSortOrder($sortOrderString)
    {
        $elements = [];
        
        $ids = explode(',', trim(trim($sortOrderString), ','));
        
        foreach ($ids as $id) {
            $elements[] = array_search($id, $this->elements);
        }
        
        return $elements;
    }
    
    /**
     * Based on provided $blockOptions that come from
     * Apptrian\Subcategories\Block\GridList class via method getCategories()
     * in this class returns array of options.
     *
     * @param string $sortOrderString
     * @return array
     */
    public function determineOptions($blockOptions)
    {
        $options        = [];
        $result         = [];
        $nameInLayout   = $blockOptions['name_in_layout'];
        $fullActionName = $blockOptions['full_action_name'];
        $bOptions       = $blockOptions['options'];
        
        $catParam       = $this->filterAndValidateCategoryId(
            $blockOptions['cat_param']
        );
        
        $categoryPageXml = strpos(
            $nameInLayout,
            'apptrian.subcategories.category.page'
        );
        
        if (0 === $categoryPageXml
            && $fullActionName = 'catalog_category_view'
        ) {
            $pageType = 'category_page';
            
            $options['category_ids'] = '';
            $options['mode']         = '';
            
            $options['exclude_ids']  = trim(
                $this->getConfig(
                    'apptrian_subcategories/category_page/exclude_ids'
                ),
                ','
            );
            
            $options['css_ident'] = 'category';
        } else {
            $pageType = 'home_page';
            
            $options['category_ids'] = trim(
                $this->getConfig(
                    'apptrian_subcategories/home_page/category_ids'
                ),
                ','
            );
            
            $options['mode']         = $this->getConfig(
                'apptrian_subcategories/home_page/mode'
            );
            
            $options['exclude_ids']  = '';
            
            if ($fullActionName == 'cms_index_index') {
                $options['css_ident'] = 'home';
            } else {
                $options['css_ident'] = 'cms';
            }
        }
        
        $options['enabled']          = $this->getConfig(
            'apptrian_subcategories/general/enabled'
        );
        
        $options['page_type']        = $pageType;
        $options['full_action_name'] = $fullActionName;
        $options['layout']           = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/layout'
        );
        
        $options['single_link']      = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/single_link'
        );
        
        $options['sort_attribute']   = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/sort_attribute'
        );
        
        $options['sort_direction']   = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/sort_direction'
        );
        
        $options['heading']          = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/heading'
        );
        
        $options['sort_order']       = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/sort_order'
        );
        
        $options['image']            = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/image'
        );
        
        $options['name']             = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/name'
        );
        
        $options['description']      = $this->getConfig(
            'apptrian_subcategories/' . $pageType . '/description'
        );
        
        // Array merge with xml config and then with block config
        if ($bOptions !== null) {
            $result = array_merge($options, $bOptions);
        } else {
            $result = $options;
        }
        
        $sortOrder            = $this->getSortOrder($result['sort_order']);
        $result['sort_order'] = $sortOrder;
        
        // If someone provides options that are not suitable
        if (0 === $categoryPageXml
            && $fullActionName = 'catalog_category_view'
        ) {
            $result['category_ids'] = '';
            $result['mode']         = '';
        } else {
            $result['exclude_ids']  = '';
        }
        
        // Add cat param to result
        $result['cat_param'] = $catParam;
        
        return $result;
    }
    
    /**
     * Based on provided $data that comes from
     * Apptrian\Subcategories\Block\GridList class returns
     * array of options and categories used in grid_list.phtml file.
     *
     * @param string $sortOrderString
     * @return array
     */
    public function getCategories($data)
    {
        $categoryModel = $this->categoryFactory->create();
        $categories    = [];
        $result        = [];
        $o             = $this->determineOptions($data);
        // Assign options property for other methods in this class
        $this->options = $o;
        
        // Get vars for easier access
        $pageType    = $o['page_type'];
        $categoryIds = $o['category_ids'];
        $mode        = $o['mode'];
        $catParam    = $o['cat_param'];
        
        // Attribute options: name, meta_title, position, and created_at
        // Direction options: asc and desc
        $sortAttribute = $o['sort_attribute'];
        $sortDirection = $o['sort_direction'];
        
        $attributesToSelect = ['name', 'url_key', 'url_path', 'image',
            'description', 'meta_description', 'meta_title'];
            
        if ($o['image'] == 'thumbnail') {
            $attributesToSelect[] = 'thumbnail';
        }
        
        // For home page and other pages when category_ids is provided
        if ($pageType == 'home_page' && $categoryIds != '') {
            // "Random" mode
            if ($mode == 'random') {
                // Get random parent ID
                $id = $this->getRandomId($categoryIds);
                
                $category = $categoryModel->load($id);

                $childrenIds = $category->getChildren();
                
                $collection = $categoryModel->getCollection()
                    ->addAttributeToSelect($attributesToSelect)
                    ->addAttributeToFilter('is_active', 1)
                    ->addAttributeToSort($sortAttribute, $sortDirection)
                    ->addIdFilter($childrenIds)
                    ->load();
                
                // Get categories array from collection
                $categories = $this->getCategoriesFromCollection($collection);
                
            // "Specific" mode
            } else {
                $collection = $categoryModel->getCollection()
                    ->addAttributeToSelect($attributesToSelect)
                    ->addAttributeToFilter('is_active', 1)
                    ->addIdFilter($categoryIds);
                
                // In this context "position" is different and must be done
                // programmatically so there is no need to sort it
                if ($sortAttribute != 'position') {
                    $collection
                        ->addAttributeToSort($sortAttribute, $sortDirection)
                        ->load();
                
                    // Get categories array from collection
                    $categories = $this->getCategoriesFromCollection(
                        $collection
                    );
                } else {
                    $collection->load();
                    
                    // Get categories array from collection sorted by
                    // categoryIDs
                    $categories = $this->getCategoriesFromCollection(
                        $collection,
                        $categoryIds
                    );
                }
            }
        
        // For category pages and home page and any other page when
        // category_ids field is empty
        } else {
            if ($pageType == 'category_page') {
                if ($catParam) {
                    $categoryId = $catParam;
                } else {
                    $currentCategory = $this->coreRegistry
                        ->registry('current_category');
                    $categoryId      = $currentCategory->getId();
                }
                
                //Is in exclude list
                if ($this->isExcluded($categoryId)) {
                    $result['categories'] = $categories;
                    $result['options']    = $o;
                    
                    return $result;
                }
            } else {
                $categoryId  = $this->storeManager
                    ->getStore()->getRootCategoryId();
            }
            
            $category    = $categoryModel->load($categoryId);
            $childrenIds = $category->getChildren();
            
            $collection = $categoryModel->getCollection()
                ->addAttributeToSelect($attributesToSelect)
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToSort($sortAttribute, $sortDirection)
                ->addIdFilter($childrenIds)
                ->load();
                
            // Get categories array from collection
            $categories = $this->getCategoriesFromCollection($collection);
        }
        
        $result['categories'] = $categories;
        $result['options']    = $o;
        
        return $result;
    }
    
    /**
     * Based on provided Category Collection and optionally sort order,
     * returns sorted array of categories.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection
     * $collection
     * @param string $sortOrder
     * @return array
     */
    public function getCategoriesFromCollection($collection, $sortOrder = '')
    {
        $categories = [];
        
        if ($sortOrder != '') {
            $sort = explode(',', $sortOrder);
            
            foreach ($sort as $id) {
                $c = $collection->getItemById($id);
                
                if ($c != null) {
                    $categories[$id] = $this->categoryToArray($c);
                }
            }
        } else {
            foreach ($collection as $c) {
                $id = $c->getId();
                
                $categories[$id] = $this->categoryToArray($c);
            }
        }
        
        return $categories;
    }
    
    /**
     * Based on provided category object returns small category array
     * with necessary data.
     *
     * @param \Magento\Catalog\Model\Category $c
     * @return array
     */
    public function categoryToArray($c)
    {
        $category = [];
        
        $category['name']        = $this->getName($c);
        $category['url']         = $this->escaper->escapeUrl($c->getUrl());
        $category['image']       = $this->getImage($c);
        $category['description'] = $this->getDescription($c);
        
        return $category;
    }

    /**
     * Returns proper name text based on provided data.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    public function getName($category)
    {
        $nameAttribute = $this->options['name'];
        
        if ($nameAttribute == 'name') {
            $categoryName = $this->escaper->escapeHtml($category->getName());
        } elseif ($nameAttribute == 'meta_title') {
            $categoryName = $this->escaper
                ->escapeHtml($category->getMetaTitle());
        } else {
            $categoryName = '';
        }
        
        return trim($categoryName);
    }
    
    /**
     * Returns proper description text based on provided data.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    public function getDescription($category)
    {
        $descriptionAttribute = $this->options['description'];
        
        if ($descriptionAttribute == 'description') {
            $description = $category->getDescription();
            
            if ($description) {
                $categoryDescription = $this->catalogHelperOutput
                    ->categoryAttribute($category, $description, 'description');
            } else {
                $categoryDescription = '';
            }
        } elseif ($descriptionAttribute == 'meta_description') {
            $categoryDescription = $this->escaper
                ->escapeHtml($category->getMetaDescription());
        } else {
            $categoryDescription = '';
        }
        
        return trim($categoryDescription);
    }
    
    /**
     * Generates image url based on provided data.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string
     */
    public function getImage($category)
    {
        $imageAttribute      = $this->options['image'];
        $placeholderImageUrl = $this->viewAssetRepo->getUrl(
            'Magento_Catalog::images/product/placeholder/small_image.jpg'
        );
        
        if ($imageAttribute == 'image') {
            $image = $category->getImage();
        } elseif ($imageAttribute == 'thumbnail') {
            $image = $category->getThumbnail();
        } else {
            $image = '';
        }
        
        if ($image != null) {
            $url = $this->getImageUrl($image);
        } else {
            $url = $placeholderImageUrl;
        }
        
        return $url;
    }
    
    /**
     * Retrieve image URL based on provided file name.
     *
     * @return string
     */
    public function getImageUrl($image)
    {
        $url = false;
        
        if ($image) {
            $url = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . 'catalog/category/' . $image;
        }
        
        return $url;
    }
    
    /**
     * Based on provided comma separated list of Ids, returns one random id.
     *
     * @param string $categoryIds
     * @return string
     */
    public function getRandomId($categoryIds)
    {
        $pool = explode(',', $categoryIds);
        
        $index = array_rand($pool, 1);
        
        return $pool[$index];
    }
    
    /**
     * Returns array of exclude Ids from config.
     *
     * @return array
     */
    public function getExcludedIds()
    {
        $excludeIds = $this->options['exclude_ids'];
        
        return explode(',', $excludeIds);
    }
    
    /**
     * Checks if category id is in excluded list.
     *
     * @param int $id
     * @return boolean
     */
    public function isExcluded($id = 0)
    {
        if ($id > 0) {
            $excluded = $this->getExcludedIds();
            
            if (!empty($excluded) && in_array($id, $excluded)) {
                return true;
            // Exclude list is empty
            } else {
                return false;
            }
            
        // Not a category page
        } else {
            return false;
        }
    }
    
    /**
     * Filters and validates "cat" url query param for layered category pages.
     *
     * @param string $id
     * @return int
     */
    public function filterAndValidateCategoryId($id)
    {
        $filterChain = new \Zend\Filter\FilterChain();
        $filterChain->attach(new \Zend\Filter\StripTags())
                    ->attach(new \Zend\Filter\StringTrim());
        
        $idFiltered = $filterChain->filter($id);
            
        $validator = new \Zend\Validator\Digits();
        
        if ($idFiltered != ''
            && $validator->isValid($idFiltered)
            && $idFiltered > 0
        ) {
            return (int) $idFiltered;
        } else {
            return 0;
        }
    }
}
