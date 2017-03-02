<?php
/**
 *
 * Copyright © 2015 Ipragmatechcommerce. All rights reserved.
 */
namespace Ipragmatech\Recoverimage\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{

	/**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
    }
	
    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();  
		return $this->resultPage;
         $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/mylog.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("Image Import controller");

        //$product_id = 323;
        $product_id = 18060;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $mediaUrl = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        //generating directory//
        $diretoryArray = explode("/", $mediaUrl);
        array_pop($diretoryArray);
        $path = null;
        for ($i = 3; $i < sizeof($diretoryArray); $i++){
            $path = $path.$diretoryArray[$i].'/';
        }
        //end direcoty generation ////
        //$dir = $path."catalog/product";
        $dir = "media/catalog/product/";
        //$dir = $path."test";

        $logger->info("Media URL".$mediaUrl);
        for ($product_id = 323 ; $product_id < 324 ; $product_id++) {
            $product = $objectManager->get('Magento\Catalog\Model\Product')->load($product_id);
            //$logger->info("Product : " . json_encode($product->getData()));

            //gallary
            $mediaGallary = $product->getMediaGallery();
            //$logger->info("Product Gallary : " . json_encode($mediaGallary));

            //images
            $images = $mediaGallary['images'];
            $productSku = $product->getSku();
            $logger->info("Product SKU : " . $productSku);
            $logger->info("Product Images : " . json_encode($images));
            try {
                //creating image array from drive
                $driveImageArray = [];
                for($i = 0; $i < 10; $i ++){
                    if($i == 0){
                        $file = $productSku.'.jpg';
                    }else{
                        $file = $productSku.'-'.$i.'.jpg';
                    }
                    $source = '/var/www/dev.toysrus.co.za/products_image/'.$file;
                    $logger->info("Creating drive img arr source : " . $source);
                    if(file_exists($source)){
                        $driveImageArray [] = $source;
                    }
                }
                $logger->info("Drive Image Array:".json_encode($driveImageArray));
                $j = 0;
                foreach ($images as $image) {
                    if($j < sizeof($driveImageArray)) {
                        $logger->info("Product Images Name: " . $image['file']);
                        $imagePathArray = explode("/", $image['file']);
                        array_shift($imagePathArray);
                        $imageFile = $dir . $image['file'];
                        $logger->info("Image File: " . $imageFile);
                        if (file_exists($imageFile)) {
                            $logger->info("got Image");
                        } else {
                            $logger->info("Image not exist copy here");
                            array_pop($imagePathArray);
                            $newpath = $dir . "/" . implode("/", $imagePathArray);
                            //$newpath = implode("/", $imagePathArray);

                            $logger->info("New path to create: " . $newpath);
                            if (!is_dir($newpath)) {
                                //Directory does not exist, so lets create it.
                                $logger->info("Directory not exist creating : " . $newpath);
                                mkdir($newpath, 0777, true);
                            }
                            //$file = $productSku.'.jpg';
                            $logger->info("Directory  exist  : " . $newpath);
                            $source = $driveImageArray[$j];

                            //$source = 'pub/media/catalog/product/w/b/wb05-red-0.jpg';
                            $dest = $imageFile; //'pub/media/test/w/b/wb05-red-0.jpg'
                            copy($source, $dest);
                        }
                        $j++;
                    }
                }
            }catch (\Exception $e){
                $logger->err("Exception >>".$e->getMessage());
            }
         } //end product loop
    }
}
