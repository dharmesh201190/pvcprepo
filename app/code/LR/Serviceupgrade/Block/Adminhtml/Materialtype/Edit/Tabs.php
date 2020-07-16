<?php
namespace LR\Serviceupgrade\Block\Adminhtml\Materialtype\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
		
        parent::_construct();
        $this->setId('serviceupgrade_materialtype_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Material Type'));
    }
}