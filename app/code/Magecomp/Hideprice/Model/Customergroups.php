<?php
namespace Magecomp\Hideprice\Model;
class Customergroups implements \Magento\Framework\Option\ArrayInterface
{
    protected $_options;
	protected $_objectManager;
	
	public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
	{
		$this->_objectManager = $objectManager;
	}
	
    public function toOptionArray()
    {
        if (!$this->_options)
		{
            $this->_options = $this->_objectManager->create('Magento\Customer\Model\ResourceModel\Group\Collection')
                ->setRealGroupsFilter()
                ->loadData()->toOptionArray();
            array_unshift($this->_options, ['value'=> '0', 'label'=>__('NOT LOGGED IN')]);
        }
        return $this->_options;
    }
}
