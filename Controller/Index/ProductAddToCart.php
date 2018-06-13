<?php

namespace Deviget\BackendMagento\Controller\Index;

class ProductAddToCart
    extends \Magento\Framework\App\Action\Action
{
    const PRODUCT_SEPARATOR = ',';

    protected $_cart;
    protected $_productFactory;
    protected $_messageManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $_productFactory
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ProductFactory $_productFactory,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_cart = $cart;
        $this->_productFactory = $_productFactory;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
        return parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute() {
        $product_id = $this->getRequest()->getParam('id');

        if (isset($product_id) && $product_id) {
            if (strstr($product_id, self::PRODUCT_SEPARATOR)) {
                $products = explode(self::PRODUCT_SEPARATOR, $product_id);
            } else {
                $products = array($product_id);
            }

            foreach ($products as $product_id) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->_productFactory->create()->load($product_id);
                if (is_object($product) && $product->getId()) {

                    try {
                        $this->_cart->addProduct($product, array('qty' => 1));
                        $this->_cart->save();
                    } catch (\InvalidArgumentException $e) {
                        $this->_messageManager->addSuccess(__("Error adding to your cart."));
                    }
                    $this->_messageManager->addSuccess(__("Product added to your cart."));
                } else {
                    $this->_messageManager->addError(__("Product does not exist."));
                }
            }
        } else {
            $this->_messageManager->addError(__("Invalid link."));
        }

        // redirect to cart
        $this->_redirect('checkout/cart');
    }
}