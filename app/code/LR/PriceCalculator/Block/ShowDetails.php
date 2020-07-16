<?php
namespace LR\PriceCalculator\Block;
use Magento\Framework\Serialize\SerializerInterface;
/**
 * Comments content block
 */
class ShowDetails extends \Magento\Framework\View\Element\Template
{
    protected $_request;
    protected $_pricecalculator;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \LR\PriceCalculator\Model\PriceCalculatorFactory $pricecalculator
    ) {
        $this->_request = $request;
        $this->_pricecalculator = $pricecalculator;
        parent::__construct($context);
    }

    public function getParams()
    {
        $params = $this->_request->getParams();
        $width = $params['width'];
        $height = $params['height'];
        $scale = $params['scale'];

        $materials = $this->_pricecalculator->create()->getCollection()->addFieldToFilter('status', 1)->addFieldToFilter('material', $params['material'])->addFieldToFilter('finish', $params['finish'])->getFirstItem();
        
        $price = '';
       // if($scale == 'meter')
     //   {
            $price = $width*$height*$materials->getPrice();
      /* }  elseif($scale == 'centi-meter') {
            $cwidth = $width*100;
            $cheight = $height*100;
            $price = $cwidth*$cheight*$materials->getPrice();
        } elseif($scale == 'milli-meter') {
            $mwidth = $width*1000;
            $mheight = $height*1000;
            $price = $mwidth*$mheight*$materials->getPrice();
        } elseif($scale == 'feet') {
            $fwidth = $width*3.281;
            $fheight = $height*3.281;
            $price = $fwidth*$fheight*$materials->getPrice();
        } else {
            $iwidth = $width*39.37;
            $iheight = $height*39.37;
            $price = $iwidth*$iheight*$materials->getPrice();
        } */

        //echo "<pre>"; print_r($materials->getFirstItem()); die();
        $params['price'] = $price; 
        //echo $materials->getPrice(); die();

        return $params; 
    }

    public function getMaterials($groupId)
    {
       $materials = $this->_pricecalculator->create()->getCollection()->addFieldToFilter('status', 1)->addFieldToFilter('material_group', $groupId);

       $materials->getSelect()->group('material'); 
       //echo $materials->getSelect()->__toString(); die();
       return $materials; 
    }
   
    /* public function getComments($id)
    {
        if($id)
        {
            $artWork = $this->_artworkdesign->create()->load($id);
            return $this->serializer->unserialize($artWork->getArtworkdesignComment());
        }
        return;
    } */
}
