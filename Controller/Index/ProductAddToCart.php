<?php
/**
 * @todo create an action for creating tokens
 * @todo finish unit and integration tests
 * @todo move createToken function to helper so the create token action can use them as well
 * @todo move constants to admin user defined parameters
 */
namespace Deviget\BackendMagento\Controller\Index;

class ProductAddToCart
    extends \Magento\Framework\App\Action\Action
{
    const PRODUCT_SEPARATOR = ',';
    const TOKEN_THRESHOLD = 15;
    const TOKEN_SALT = 'DEVIGET';

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
     * Creates a token for the product
     *
     * @param $product_id
     * @param null $time
     * @return string
     */
    protected function createToken($product_id, $time = null) {
        if (empty($time)) {
            $time = date("Y-m-d H:i:00");
        }

        $salt = $this::TOKEN_SALT;

        return md5($product_id.$time.$salt);
    }

    /**
     * Validates, given a product id an a token, if it's a valid token
     *
     * @param $product_id
     * @param string $token
     * @return bool
     */
    protected function validateUrl($product_id, $token) {
        $valid_url = false;
        for ($i=0; $i < $this::TOKEN_THRESHOLD; $i++) {
            $time = date("Y-m-d H:00:00", strtotime('+'.$i.' minutes'));

            $valid_url |= ($token == $this->createToken($product_id, $time));
        }
        return $valid_url;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute() {
        $product_id = $this->getRequest()->getParam('id');
        $token = $this->getRequest()->getParam('token');

        if (isset($product_id) && $product_id) {
            if (isset($token) && $this->validateUrl($product_id, $token)) {
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
                $this->_messageManager->addError(__("This link has expired or is invalid."));
            }

        } else {
            $this->_messageManager->addError(__("Invalid link."));
        }

        // redirect to cart
        $this->_redirect('checkout/cart');
    }
}