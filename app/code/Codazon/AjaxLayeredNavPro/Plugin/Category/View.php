<?php
/**
 * Copyright © 2017 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Codazon\AjaxLayeredNavPro\Plugin\Category;

class View 
{
    protected $helper;
    
    public function __construct(
        \Codazon\AjaxLayeredNavPro\Helper\Data $helper,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $cacheStage
    ) {
        $this->helper = $helper;
        $this->_cache = $cache;
        $this->_cacheStage = $cacheStage;
    }
    
    public function getResult($controller, $page){
        $request = $controller->getRequest();
        $layout = $page->getLayout();
        $result = [];
        if ($block = $layout->getBlock('category.products')) {
            if($this->_cacheStage->isEnabled('block_html')){
                $key = $controller->getRequest()->getParams();
                $key[] = 'category_products';
                $key = md5(serialize($key));
                $result['category_products'] = $this->_cache->load($key);
                $result['category_products'] = $result['category_products'];
                if($result['category_products'] == ""){
                    $result['category_products'] = $block->toHtml();
                    $this->_cache->save($result['category_products'],$key,['block_html'],null);
                }
            }else{
                $result['category_products'] = $block->toHtml();
            }
        }
        if ($block = $layout->getBlock('catalog.leftnav')) {
            $result['catalog_leftnav'] = $block->toHtml();
        }
        if ($block = $layout->getBlock('page.main.title')) {
            $result['page_main_title'] = $block->toHtml();
        }
        $filterManager = $this->helper->getFilterManager();
        $queryValue = $request->getQueryValue();
        $newQueryValue = $queryValue;
        if ($block = $layout->getBlock('catalog.navigation.state')) {
            $filters = $block->getActiveFilters();
            $urlParams = [];
            foreach($filters as $filter) {
                $filterModel = $filter->getFilter();
                $code = $filterModel->getRequestVar();
                if (isset($newQueryValue[$code])) {
                    $class = get_class($filterModel);
                    if ($class == 'Magento\CatalogSearch\Model\Layer\Filter\Attribute' || $class == 'Codazon\AjaxLayeredNavPro\Model\Layer\Filter\Attribute') {
                        $label = $filter->getLabel();
                        if (is_array($label)) {
                            $newQueryValue[$code] = [];
                            foreach ($label as $lb) {
                                $newQueryValue[$code][] = $filterManager->translitUrl(htmlspecialchars_decode($lb));
                            }
                            $newQueryValue[$code] = trim(implode(',', $newQueryValue[$code]));
                        } else {
                            $newQueryValue[$code] = $filterManager->translitUrl(htmlspecialchars_decode($label));
                        }
                    } elseif ($class == 'Magento\CatalogSearch\Model\Layer\Filter\Category') {
                        $newQueryValue[$code] = $newQueryValue[$code].'_'.$filterManager->translitUrl(htmlspecialchars_decode($filter->getLabel()));
                    }
                }
            }
            
            if (isset($newQueryValue['cat'])) {
                if ($request->getParam('id') == $newQueryValue['cat']) {
                    $newQueryValue['cat'] = null;
                }
            }
            $result['updated_url'] = $block->getUrl('*/*/*', [
                                                    '_current'      => true,
                                                    '_query'        => $newQueryValue,
                                                    '_use_rewrite'  => true,
                                                    ]);
            $result['updated_url'] = str_replace('%2C', ',', $result['updated_url']);
        }
        return $result;
    }
    
    public function afterExecute(\Magento\Catalog\Controller\Category\View $controller, $page)
    {
        if ($controller->getRequest()->getParam('ajax_nav')) {
            $page->initLayout();
            $result = $this->getResult($controller, $page);
            $json = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\JsonFactory')->create();
            $json->setData($result);
            return $json;
        } else {
            return $page;
        }
    }
}
