<?php
/**
 * @category  Apptrian
 * @package   Apptrian_Subcategories
 * @author    Apptrian
 * @copyright Copyright (c) 2017 Apptrian (http://www.apptrian.com)
 * @license   http://www.apptrian.com/license Proprietary Software License EULA
 */
 
namespace Apptrian\Subcategories\Block;

class GridList extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Apptrian\Subcategories\Helper\Data
     */
    public $helper;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Apptrian\Subcategories\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Apptrian\Subcategories\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        
        parent::__construct($context, $data);
    }
    
    /**
     * Used in .phtml file and returns array of options and categories.
     *
     * @return array
     */
    public function getSubcategoriesData()
    {
        $data = [];
        
        $data['name_in_layout']   = $this->getNameInLayout();
        $data['full_action_name'] = $this->getRequest()->getFullActionName();
        $data['cat_param']        = $this->getRequest()->getParam('cat');
        $data['options']          = null;
        
        $configOptions  = $this->getConfigOptions();
        $encodedOptions = $this->getEncodedOptions();
        
        if ($configOptions !== null) {
            $data['options'] = $configOptions;
        }
        
        if ($encodedOptions !== null) {
            $data['options'] = $this->helper->decode($encodedOptions);
        }
        
        return $this->helper->getCategories($data);
    }
}
