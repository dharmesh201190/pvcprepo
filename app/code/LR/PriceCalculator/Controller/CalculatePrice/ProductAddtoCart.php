<?php
namespace LR\PriceCalculator\Controller\CalculatePrice;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;

class ProductAddtoCart extends Action
{
    protected $formKey;   
    protected $cart;
    protected $product;
    protected $serializer;

    public function __construct
    (
        Context $context,
        FormKey $formKey,
        Cart $cart,
        Product $product,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->product = $product;
        $this->serializer = $serializer;      
        parent::__construct($context);
    }

    public function execute()
    { 
        $post = $this->getRequest()->getParams();
        $productId = 41161;
        $product = $this->product->load($productId);
        $product->setPrice($post['price']);
        $product->setFinalPrice($post['price']);
        $additionalOptions = array();
        $additionalOptions['scale'] = [
            'label' => 'Scale',
            'value' => $post['scale'],
        ];
        $additionalOptions['width'] = [
            'label' => 'Width',
            'value' => $post['width'],
        ];
        $additionalOptions['height'] = [
            'label' => 'Height',
            'value' => $post['height'],
        ];
        $additionalOptions['material'] = [
            'label' => 'Material',
            'value' => $post['material'],
        ];
        $additionalOptions['finish'] = [
            'label' => 'Finish',
            'value' => $post['finish'],
        ];
        $product->addCustomOption('additional_options', $this->serializer->serialize($additionalOptions));
        $params = array(
                    'form_key' => $this->formKey->getFormKey(),
                    'product' => $productId, 
                    'qty'   => $post['qty'],
                    'price' => $post['price']
                );              
               
        try{
            $this->cart->addProduct($product, $params);
            $this->cart->save();
            $this->messageManager->addSuccessMessage(__("Product added to cart successfully."));
                $this->_redirect('checkout/cart');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("We can\'t process your request right now. Sorry, that\'s all we know.".$e->getMessage()));
                $this->_redirect();
                return;
            }

     }
}