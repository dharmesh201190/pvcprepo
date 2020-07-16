<?php
namespace LR\Serviceupgrade\Block\Adminhtml\Holidays\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
		
        parent::_construct();
        $this->setId('serviceupgrade_holidays_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Production Holidays'));
    }
}