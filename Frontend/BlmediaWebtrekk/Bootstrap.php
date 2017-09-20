<?php
/**
 * Class Shopware_Plugins_Backend_BlmediaWebtrekk_Bootstrap
 *
 * @author BLmedia GmbH
 * @category  Checkout
 * @package   Shopware\Plugins\BlmediaWebtrekk
 * @copyright Copyright (c) 2016, BLmedia GmbH (http://www.blmedia.de)
 *
 */
class Shopware_Plugins_Frontend_BlmediaWebtrekk_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var array
     */
    var $_arWebtrekkConfig = array();

    /**
     * @var array
     */
    var $_arDatalayerKeyValuePairs = array();

    /**
     * @var array
     */
    var $_arPageId = array();

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    var $oView;

    /**
     * @var
     */
    var $oRequest;

    /**
     * @var
     */
    var $_actionName;


    /**
     * Installs the plugin
     *
     * @return bool
     */
    public function install()
    {
        $this->createForm();
        $this->registerEvents();
        return true;
    }

    /**
     * Registers the Event during the install process
     *
     * @return bool
     */
    function registerEvents(){
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Frontend', 'onPostDispatch');
        return true;
    }

    /**
     * Updates the Plugin
     *
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * Returns the developer Information
     *
     * @return array
     */
    public function getInfo(){
        return array(
            'version' => $this->getVersion(),
            'autor' => 'BLmedia GmbH',
            'label' => $this->getLabel(),
            'source' => "Local",
            'copyright' => 'Copyright © ' . date('U') . ', BLmedia GmbH',
            'support' => 'support@blmedia.de',
            'link' => 'http://www.blmedia.de/',
            'description' => file_get_contents($this->Path() . 'info.txt')
        );
    }

    /**
     * Returns the current Versioin of this Plugin
     * @returns string
     */
    public function getVersion()
    {
        return "1.0.3";
    }

    /**
     * Returns the name of the plugin
     * @return string
     */
    public function getLabel()
    {
        return "Webtrekk Datalayer Integration";
    }

    /**
     * Sets the arguments which defines weather the plugin is installable, updateable and enableable or not
     *
     * @return array
     */
    public function getCapabilities(){
        return array(
            'install' => true,
            'update' => true,
            'enable' => true
        );
    }

    /**
     *
     * @return bool
     */
    function createForm(){
        $form = $this->Form();

        $parent = $this->Forms()->findOneBy(array('name' => 'Frontend'));
        $form->setParent($parent);

        $form->setElement('boolean', 'activatePlugin',
            array(
                'label' => 'Activate Plugin',
                'value' => 1,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement('text', 'wt_safetagId',
            array(
                'label' => 'TagIntegration ID',
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement('text', 'wt_safetagDomain',
            array(
                'label' => 'TagIntegration Domain:',
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement('text', 'wt_customDomain',
            array(
                'label' => 'Custom Domain:',
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement('text', 'wt_customPath',
            array(
                'label' => 'Custom Path:',
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement('textarea', 'wt_attribute_blacklist',
            array(
                'label' => 'Attribute Blacklist',
                'description' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        return true;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    function addWebtrekkConfigKeyValuePair($key, $value){
        if($value==='')
            return false;

        $this->_arWebtrekkConfig[$key] = (string)$value;

        return true;
    }

    /**
     * @param $key
     * @param $value
     */
    function setDatalayerKeyValuePair($key, $value){
        $this->_arDatalayerKeyValuePairs[$key] = $this->toString($value);
    }

    /**
     * @return array
     */
    function getWebtrekkConfigArray(){
        return $this->_arWebtrekkConfig;
    }

    /**
     * @return array
     */
    function getDatalayer(){
        $this->_arDatalayerKeyValuePairs['pageId'] = implode('.', $this->getPageIdArray());
        $dataLayerItems = $this->deleteBlacklistAttribute($this->_arDatalayerKeyValuePairs);
        $dataLayerFlattenItems = $this->flattenArray($dataLayerItems, '', '_');

        return $this->deleteBlacklistAttribute($dataLayerFlattenItems);
    }

    /**
     * @param $array
     * @return array
     */
    function deleteBlacklistAttribute($array) {
        $blacklist = $this->Config()->wt_attribute_blacklist;

        if(empty($blacklist) == false) {
            $arBlacklistElements = explode(";", $blacklist);
            foreach($arBlacklistElements AS $element){
                if(array_key_exists($element, $array)) {
                    unset($array[$element]);
                }
            }
        }

        return $array;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    function appendDatalayerParameterKeyValuePair($key, $value){
        if($value==='')
            return false;

        if(isset($this->_arDatalayerKeyValuePairs[$key])==false){
            $this->_arDatalayerKeyValuePairs[$key] = (string)$value;
        }else{
            $this->_arDatalayerKeyValuePairs[$key] = $this->_arDatalayerKeyValuePairs[$key] . ';' . $value;
        }

        return true;
    }

    /**
     * @param $value
     * @param bool $bResetArray
     */
    function addPageIdValue($value, $bResetArray=false){
        if($bResetArray)
            $this->_arPageId = array();

        $value = str_replace('.', '', $value);

        $this->_arPageId[] = strtolower($value);
    }

    /**
     * @return array
     */
    function getPageIdArray(){
        return $this->_arPageId;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function onPostDispatch(Enlight_Event_EventArgs $arguments){
        $config = $this->Config();
        if(empty($config->activatePlugin) == true)
            return true;

        $controller = $arguments->getSubject();
        $this->oRequest = $controller->Request();
        $this->oView = $controller->View();
        $controllerName = $this->Request()->getControllerName();
        $this->_actionName = $this->Request()->getActionName();

        // Set Template Dir and Template
        $this->View()->addTemplateDir($this->Path() . 'Views/');
        $this->View()->extendsTemplate('frontend/plugins/BlmediaWebtrekk/index.tpl');

        // Basic Konfiguration
        ///////////////////////////////////////////////////////////////

        // Safetag ID
        $this->addWebtrekkConfigKeyValuePair('tiId', $config->wt_safetagId);

        // Safetag Path
        $this->addWebtrekkConfigKeyValuePair('tiDomain', $config->wt_safetagDomain);

        // Custom ID
        $this->addWebtrekkConfigKeyValuePair('customDomain', $config->wt_customDomain);

        // Custom Path
        $this->addWebtrekkConfigKeyValuePair('customPath', $config->wt_customPath);

        // PageId
        $this->setPageIdBase($controllerName);

        // Set Language
        $this->setDatalayerKeyValuePair('pageLanguage', $this->getLanguage());

        // Set Current Payment
        $this->setCurrentSelectedPaymentMethode();

        // Current User
        $userEmailAddress = $this->getCurrentCustomerEmailAddress();
        if($userEmailAddress !== false){
            $this->setDatalayerKeyValuePair('userId', md5($userEmailAddress));
            $this->setDatalayerKeyValuePair('isLoggedIn', '1');
            $userData = $this->getUserData();
            unset($userData['additional']['user']['password']);
            unset($userData['additional']['user']['encoder']);
            $this->setDatalayerKeyValuePair('userData', $userData);
        }

        $arControllers = array('detail', 'newsletter', 'listing', 'index', 'register', 'search', 'note', 'account', 'blog', 'checkout', 'campaign', 'forms', 'custom');
        foreach($arControllers AS $k){
            if($k != $controllerName)
                continue;

            $methodName = '_' . $k . 'Controller';
            if(is_callable(array($this, $methodName))==true){
                $this->$methodName();
            }
        }

        // ContentType
        $this->setDatalayerKeyValuePair('pageType', $this->getPageType());

        // Webtrekk Config Arrays
        $webtrekk_global_config = $this->getWebtrekkConfigArray();
        $this->View()->assign('blWebtrekkConfig', $webtrekk_global_config);

        // Webtrekk Datalayer
        $this->View()->assign('blWebtrekkDatalayerAsJson', json_encode($this->getDatalayer(), JSON_UNESCAPED_UNICODE));

        return true;
    }

    /**
     *
     */
    function setCurrentSelectedPaymentMethode(){
        if(Shopware()->Session()->sOrderVariables->sPayment){
            $paymentMethod = Shopware()->Session()->sOrderVariables->sPayment['name'];
            $this->setDatalayerKeyValuePair('SelectedPaymentMethod', $paymentMethod);
        }
    }

    /**
     * @var string
     */
    var $_pageType = 'unknown';

    /**
     * @return string
     */
    function getPageType(){
        return $this->_pageType;
    }

    /**
     * @param $pageType
     */
    function setPageType($pageType){
        $this->_pageType = $pageType;
    }

    /**
     * @param $controllerName
     */
    function setPageIdBase($controllerName){
        $categoryId = $this->Request()->getParam('sCategory');

        $arCategories = array();

        $arBreadcrumbs = $this->View()->getAssign('sBreadcrumb');
        foreach($arBreadcrumbs AS $arEntry){
            $arCategories[] = $arEntry;
        }

        if($controllerName=='listing' AND empty($categoryId)==true){
            $arCategories[] = array(
                "id" => 0,
                "name" => 'home'
            );
        }

        $this->addPageIdValue($this->getLanguage());
        foreach ($arCategories AS $arCategory) {
            $this->addPageIdValue($arCategory['name']);
        }
    }

    /**
     *
     */
    public function _blogController() {
        $this->setPageType('Blog content');

        if($this->getActionName()=='detail'){
            $this->addPageIdValue($this->View()->sArticle['title']);
        }
    }

    /**
     *
     */
    public function _campaignController() {
        $this->setPageType('Editorial content');

        $sBreadcrumb = $this->View()->getAssign('sBreadcrumb');
        $this->addPageIdValue($sBreadcrumb[0]['name']);
    }

    /**
     *
     */
    public function _indexController() {
        $this->setPageType('Category page');

        $this->addPageIdValue($this->getLanguage(), true);
        $this->addPageIdValue('home');
    }

    /**
     *
     */
    public function _formsController() {
        $this->setPageType('Contactform');
        $this->addPageIdValue($this->getLanguage(), true);
        $this->addPageIdValue($this->View()->sSupport["name"]);
    }

    /**
     *
     */
    public function _registerController(){
        $this->setPageType('Account');
        $this->addPageIdValue($this->getLanguage(), true);

        $arMappingTable = array();
        $arMappingTable['saveRegister'] = 'Account.Order overview';
        $arMappingTable['index'] = 'Login';
        $arMappingTable['default'] = 'Account.' . $this->_actionName;

        $name = isset($arMappingTable[$this->_actionName]) ? $arMappingTable[$this->_actionName] : $arMappingTable['default'] ;
        list($part1, $part2) = explode('.', $name);
        $this->addPageIdValue($part1);
        $this->addPageIdValue($part2);
    }

    /**
     *
     */
    public function _searchController(){
        $this->setPageType('Search page');
        $this->addPageIdValue($this->getLanguage(), true);
        $this->addPageIdValue("search");

        $this->setDatalayerKeyValuePair('searchTerm', $this->Request()->getParam('sSearch'));

        $arSearchResults = $this->View()->getAssign('sSearchResults');
        $this->setDatalayerKeyValuePair('searchResults', $arSearchResults['sArticlesCount']);
    }

    /**
     *
     */
    public function _accountController(){
        $this->setPageType('Account');
        $this->addPageIdValue($this->getLanguage(), true);

        $arMappingTable = array();
        $arMappingTable['index'] = 'Login.Overview';
        $arMappingTable['orders'] = 'Login.Orders';
        $arMappingTable['billing'] = 'Login.Billing address';
        $arMappingTable['shipping'] = 'Login.Shipping address';
        $arMappingTable['payment'] = 'Login.Payment method';
        $arMappingTable['downloads'] = 'Login.Downloads';
        $arMappingTable['password'] = 'Login.Forgot password';
        $arMappingTable['default'] = 'Login.' . $this->_actionName;

        $name = isset($arMappingTable[$this->_actionName]) ? $arMappingTable[$this->_actionName] : $arMappingTable['default'] ;

        list($part1, $part2) = explode('.', $name);
        $this->addPageIdValue($part1);
        $this->addPageIdValue($part2);
    }

    /**
     *
     */
    public function _listingController(){
        $this->setPageType('Category page');

        // Set Category
        $arBreadcrumbs = $this->View()->getAssign('sBreadcrumb');
        foreach($arBreadcrumbs AS $k => $arEntry){
            $keyname = 'pageCat_' . ($k + 1);
            $this->setDatalayerKeyValuePair($keyname, $arEntry['name']);
        }
    }

    /**
     *
     */
    public function _newsletterController(){
        $this->setPageType('Newsletter');

        if(isset($_POST['subscribeToNewsletter']) AND $_POST['subscribeToNewsletter']==1)
            $this->addPageIdValue('subscribe');
    }

    /**
     *
     */
    public function _customController(){
        $this->setPageType('Editorial content');

        $sCustomPage = $this->View()->getAssign('sCustomPage');

        if(isset($sCustomPage['parent'])==true){
            $this->addPageIdValue($sCustomPage['parent']['description']);
        }

        $this->addPageIdValue($sCustomPage['description']);

    }

    /**
     *
     */
    public function _noteController(){
        $this->setPageType('Account');
        $this->addPageIdValue('Login');
        $this->addPageIdValue('Wishlist');
    }

    /**
     *
     */
    public function _detailController(){
        $this->setPageType('Product details');

        $arArticle = $this->View()->getAssign('sArticle');

        $this->setDatalayerKeyValuePair('productId', $arArticle['ordernumber']);
        $this->setDatalayerKeyValuePair('productName', $arArticle['articleName']);
        $this->setDatalayerKeyValuePair('productDescription', $arArticle['description']);
        $this->setDatalayerKeyValuePair('productPrice', $this->c2p($arArticle['price']));
        $this->setDatalayerKeyValuePair('productQuantity', 1);
        $this->setDatalayerKeyValuePair('currency', $this->Shop()->getCurrency()->getCurrency());
        $this->setDatalayerKeyValuePair('productStatus', 'view');

        $this->setDatalayerKeyValuePair('productAttributes', $this->getDetailArticleAttributes($arArticle['attributes']['core']));

        $this->setDatalayerKeyValuePair('productVariantData', $this->getDetailArticleVariant($arArticle['sConfigurator']));

        $this->setDatalayerKeyValuePair('productImageS', $this->getDetailArticleImages($arArticle['image'], 0));
        $this->setDatalayerKeyValuePair('productImageM', $this->getDetailArticleImages($arArticle['image'], 1));
        $this->setDatalayerKeyValuePair('productImageL', $this->getDetailArticleImages($arArticle['image'], 2));

        $arWhiteList = array();
        $arWhiteList[] = 'tax';
        $arWhiteList[] = 'instock';
        $arWhiteList[] = 'isAvailable';
        $arWhiteList[] = 'esd';
        $arWhiteList[] = 'weight';
        $arWhiteList[] = 'length';
        $arWhiteList[] = 'width';
        $arWhiteList[] = 'laststock';
        $arWhiteList[] = 'additionaltext';
        $arWhiteList[] = 'datum';
        $arWhiteList[] = 'metaTitle';
        $arWhiteList[] = 'ean';
        $arWhiteList[] = 'keywords';
        $arWhiteList[] = 'sReleasedate';

        foreach($arWhiteList AS $key){
            if(array_key_exists($key, $arArticle)==false)
                continue;

            $this->setDatalayerKeyValuePair($key, $arArticle[$key]);
        }

        $this->addPageIdValue($arArticle['ordernumber']);

        $this->setDatalayerKeyValuePair('supplierNumber', $arArticle['suppliernumber']);
        $this->setDatalayerKeyValuePair('supplierName', $arArticle['supplierName']);
        $this->setDatalayerKeyValuePair('supplierID', $arArticle['supplierID']);

        $canonicalCategoryTree = null;

        $arCategoriesTree = $this->getCategoryTreesByArticleId($arArticle['articleID']);
        foreach($arCategoriesTree AS $kOuter => $arTree){
            foreach($arTree AS $kInner => $arCategory){
                $dataLayerKey = 'productCat_' . ($kOuter+1) . '_' . ($kInner+1);
                $this->setDatalayerKeyValuePair($dataLayerKey, $arCategory['name']);
                if($arCategory['isCanonical']==1){
                    $canonicalCategoryTree = $arTree;
                }
            }

            if($canonicalCategoryTree==null){
                $canonicalCategoryTree = $arTree;
            }
        }

        if($canonicalCategoryTree!==null){
            foreach($canonicalCategoryTree AS $k => $arCategory){
                $this->setDatalayerKeyValuePair('productCat_Canonical_' . ($k+1), $arCategory['name']);
            }
        }

        if($this->getActionName()=='index') {
            $this->View()->extendsTemplate('frontend/plugins/BlmediaWebtrekk/detail.tpl');
        }

        if($this->Request()->getParam('template')=='ajax'){
            $this->View()->extendsTemplate('frontend/plugins/BlmediaWebtrekk/detail_ajax.tpl');
        }
    }

    /**
     * @param \Shopware\Bundle\StoreFrontBundle\Struct\Attribute $oAttributes
     * @return array
     */
    function getDetailArticleAttributes(\Shopware\Bundle\StoreFrontBundle\Struct\Attribute $oAttributes){
        $arAttributes = $oAttributes->toArray();

        $blacklist = $this->Config()->wt_attribute_blacklist;

        if(empty($blacklist)==true)
            return $arAttributes;

        $arBlacklistElements = explode("\n", $blacklist);
        foreach($arBlacklistElements AS $element){
            if(array_key_exists($element, $arAttributes))
                unset($arAttributes[$element]);
        }

        return $arAttributes;
    }

    /**
     * @param $arConfigurator
     * @return string
     */
    function getDetailArticleVariant($arConfigurator){
        $arReturn = array();

        foreach($arConfigurator AS $arGroup){
            foreach($arGroup AS $arOption){
                if($arOption['selected']==1)
                    $arReturn[] = $arGroup['groupname'] . ': ' . $arOption['optionname'];

            }
        }

        return implode(' / ', $arReturn);
    }

    /**
     * @param $arImages
     * @param $index
     * @return bool
     */
    function getDetailArticleImages($arImages, $index){
        if(array_key_exists($index, $arImages['thumbnails'])==false)
            return false;

        return $arImages['thumbnails'][$index]['source'];
    }

    /**
     *
     */
    public function _checkoutController(){
        $this->setPageType('Buying process');
        $this->addPageIdValue($this->getLanguage(), true);

        $this->addPageIdValue('checkout');
        $this->addPageIdValue($this->getActionName());

        // die($this->getActionName() . '|');
        if($this->getActionName() == 'ajaxCart'){
            $this->View()->extendsTemplate('frontend/plugins/BlmediaWebtrekk/checkout_add_to_basket.tpl');
        }

        if($this->getActionName() == 'finish'){
            // Order
            $this->setDatalayerKeyValuePair('productStatus', 'conf');
            $this->setDatalayerKeyValuePair('orderId', $this->View()->getAssign('sOrderNumber'));
            $this->setDatalayerKeyValuePair('orderValue', $this->View()->getAssign('sAmount'));

            // User-Data
            $arUserData = $this->getUserData();

            $bIsGuestOrder = $arUserData['additional']['user']['accountmode'] == 1;
            $customerEmailAddress = $arUserData['additional']['user']['email'];

            if($bIsGuestOrder){
                $this->setDatalayerKeyValuePair('customerId', md5($customerEmailAddress));
            }
            $this->setDatalayerKeyValuePair('bIsGuestOrder', 1);

            $arUserData['billingaddress']['country'] = $arUserData['additional']['country']['countryiso'];
            $arUserData['shippingaddress']['country'] = $arUserData['additional']['countryShipping']['countryiso'];
            $this->setDatalayerKeyValuePair('billingAddress', $arUserData['billingaddress']);
            $this->setDatalayerKeyValuePair('shippingAddress', $arUserData['shippingaddress']);

            // Basket
            $arDiscounts = array();
            $arVouchers = array();

            $arBasket = $this->View()->getAssign('sBasket');

            foreach($arBasket['content'] AS $k => $arItem){
                $arItem['articlename'] = str_replace(array('Abschlag für Zahlungsart', 'Zuschlag für Zahlungsart'), 'Payment', $arItem['articlename']);

                $arBasket['content'][$k] = $arItem;

                if($arItem['priceNumeric'] > 0)
                    continue;

                $arDiscounts[] = abs($arItem['priceNumeric']);
                $arVouchers[] = $arItem['articlename'];
            }

            if(count($arDiscounts) > 0) {
                $couponValue = round(array_sum($arDiscounts), 2);
                $this->setDatalayerKeyValuePair('couponValue', $couponValue);
                $this->setDatalayerKeyValuePair('couponNames', $this->safeImplode($arVouchers));
            }

            // Shipping
            $arVersand = $this->View()->getAssign('sDispatch');
            $this->setDatalayerKeyValuePair('deliveryName', $arVersand['name']);

            $arShippingBasketRow = array();
            $arShippingBasketRow['articlename'] = 'Delivery';
            $arShippingBasketRow['ordernumber'] = '';
            $arShippingBasketRow['quantity'] = 1;
            $arShippingBasketRow['priceNumeric'] = $arBasket['sShippingcostsWithTax'];
            $arBasket["content"][] = $arShippingBasketRow;

            // Payment
            $this->setDatalayerKeyValuePair('paymentName', $this->getPaymentName());

            $this->_assignFlattenBasket($arBasket["content"]);
        }
    }

    /**
     * @return mixed
     */
    function getActionName(){
        return $this->_actionName;
    }

    /**
     * @return bool
     */
    function isSw5(){
        return Shopware()->Shop()->getTemplate()->getVersion() >= 3;
    }

    /**
     * @return mixed
     */
    function getPaymentName(){
        $arUserData = $this->View()->getAssign('sUserData');
        return $arUserData['additional']['payment']['name'];
    }

    /**
     * @return bool|mixed
     */
    private function getCurrentCustomerId(){
        if(empty(Shopware()->Session()->sUserId)==true)
            return false;

        return Shopware()->Session()->sUserId;
    }

    /**
     * @return array|false
     */
    function getUserData(){
        return Shopware()->Modules()->Admin()->sGetUserData();
    }

    /**
     * @return bool
     */
    function getCurrentCustomerEmailAddress(){
        if($this->getCurrentCustomerId()==false)
            return false;

        $userData = $this->getUserData();
        return $userData['additional']['user']['email'];
    }

    /**
     * @return mixed
     */
    function getLanguage(){
        return Shopware()->Locale()->getLanguage();
    }

    /**
     * @param $categoryId
     * @param array $arReturn
     * @return array
     */
    public function getCategoryTree($categoryId, $arReturn=array()){
        $rootCategoryId = Shopware()->Shop()->get('parentID');

        $sql ="   SELECT description, parent, id
                  FROM s_categories
                  WHERE id=?";
        $arResult = Shopware()->Db()->fetchRow($sql, array($categoryId));

        if($categoryId == $rootCategoryId){
            $arReturn[] = array(
                "id" => 0,
                "name" => $this->getLanguage()
            );
            return $arReturn;
        }

        if($arResult["description"]!='') {
            $arReturn[] = array(
                "id" => $arResult["id"],
                "name" => $arResult["description"]
            );
        }

        if($arResult["parent"] < 2){
            $arReturn[] = array(
                "id" => 0,
                "name" => $this->getLanguage()
            );
            return $arReturn;
        }

        return $this->getCategoryTree($arResult["parent"], $arReturn);
    }

    /**
     * @return array
     */
    public function Basket(){
        return Shopware()->Modules()->Basket()->sGetBasket();
    }

    /**
     * Returns view instance
     *
     * @return Enlight_View_Default
     */
    function View(){
        return $this->oView;
    }

    /**
     * Returns request instance
     *
     * @return Enlight_Controller_Request_Request
     */
    function Request(){
        return $this->oRequest;
    }

    /**
     * @param $value
     * @return array|string
     */
    function toString($value){
        if(is_bool($value)){
            $value = $value ? '1' : '0';
        }

        if(is_array($value)){
            foreach($value AS $k => $v){
                $value[$k] = $this->toString($v);
            }
            return $value;
        }else{
            return (string)$value;
        }
    }

    /**
     * @param $value
     * @return string
     */
    function _string2bool($value){
        $value = (string)$value;
        return ($value=='1' OR $value=='true') ? 'true' : 'false' ;
    }

    /**
     * @param $arValues
     * @param string $separator
     * @return string
     */
    function safeImplode($arValues, $separator=';'){
        $arReturn = array();
        foreach($arValues AS $value){
            $arReturn[] = $this->sanitizeString($value);
        }
        return implode($separator, $arReturn);
    }

    /**
     * @param $string
     * @param string $separator
     * @return array
     */
    function _stringToArray($string, $separator='.'){
        $arReturn = array();
        $arParts = explode($separator, $string);
        foreach($arParts AS $k => $value){
            $index = $k+1;
            $arReturn[$index] = $value;
        }
        return $arReturn;
    }

    /**
     * @param $arRows
     */
    function _assignFlattenBasket($arRows){
        $arValues = $this->_getImplodedValuesByKey($arRows, array('ordernumber', 'articlename', 'additional_details.image.src.original', 'quantity', 'priceNumeric'));

        $this->setDatalayerKeyValuePair('productId', $arValues['ordernumber']);
        $this->setDatalayerKeyValuePair('productName', $arValues['articlename']);
        $this->setDatalayerKeyValuePair('productQuantity', $arValues['quantity']);
        $this->setDatalayerKeyValuePair('productPrice', $arValues['priceNumeric']);
        $this->setDatalayerKeyValuePair('productImage', $arValues['additional_details.image.src.original']);
    }

    /**
     * @param $arData
     * @param string $separator
     * @return string
     */
    function implodeArray($arData, $separator='.'){
        $arReturn = array();

        foreach($arData AS $string){
            $arReturn[] = $this->sanitizeString($string);
        }

        return implode($separator, $arReturn);
    }

    /**
     * @param $arData
     * @param $arKeys
     * @return array
     */
    function _getImplodedValuesByKey($arData, $arKeys){
        $arReturn = $arReturnImploded = array();
        foreach($arData AS $arRow){
            foreach($arRow AS $k => $value){
                if(in_array($k, $arKeys)==false)
                    continue;

                $arReturn[$k][] = $this->sanitizeString($value);
            }
        }

        foreach($arReturn AS $k => $arValues){
            $arReturnImploded[$k] = implode(';', $arValues);
        }

        return $arReturnImploded;
    }

    /**
     * @param $array
     * @param string $prefix
     * @param string $connector
     * @return array
     */
    function flattenArray($array, $prefix = '', $connector='.'){
        $result = array();

        foreach ($array as $key => $value){
            $new_key = $prefix . (empty($prefix) ? '' : $connector) . $key;

            if (is_array($value)){
                $result = array_merge($result, $this->flattenArray($value, $new_key, $connector));
            }else{
                $result[$new_key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param $sStr
     * @return mixed
     */
    public function sanitizeString($sStr){
        $nbsp = chr(0xa0);
        $sStr = str_replace( $nbsp, " ", $sStr );
        $sStr = str_replace( "\"", "", $sStr );
        $sStr = str_replace( "'", "", $sStr );
        $sStr = str_replace( "%", "", $sStr );
        $sStr = str_replace( ",", "", $sStr );
        $sStr = str_replace( ";", "", $sStr );

        return $sStr;
    }

    /**
     * @param $number
     * @return float
     */
    function c2p($number){
        $number = str_replace(',', '.', $number);
        $number = str_replace(' EUR', '', $number);
        return floatval($number);
    }

    /**
     * Returns config instance
     *
     * @return \Shopware\Models\Shop\Shop
     */
    function Shop(){
        return Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext()->getShop();
    }

    /**
     * @return Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    function Db(){
        return Shopware()->Container()->get('db');
    }

    /**
     * @param $articleId
     * @return array
     */
    public function getCategoryTreesByArticleId($articleId){
        $categorieRepository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $shopId = $this->Shop()->getId();
        $shopRootId = $this->Shop()->getCategory()->getId();

        $canonicalCategoryId = (int)$this->Db()->fetchOne(
            'SELECT category_id
             FROM s_articles_categories_seo
             WHERE article_id = :articleId
             AND shop_id = :shopId',
            array(':articleId' => $articleId, ':shopId' => $shopId)
        );

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('categories'))
            ->from('Shopware\Models\Category\Category', 'categories', 'categories.id')
            ->andWhere(':articleId MEMBER OF categories.articles')
            ->setParameters(array('articleId' => $articleId));
        $result = $builder->getQuery()->getArrayResult();

        $categories = array();
        foreach ($result as $item) {
            $path = $categorieRepository->getPathById($item['id'], 'name', null);

            $pathKeys = array_keys($path);

            if(in_array($shopRootId, $pathKeys)==false)
                continue;

            $arCategory = array();
            foreach($path AS $k => $value){
                $bIsCanonical = ($k==$canonicalCategoryId) ? 1 : 0 ;
                $arCategory[] = array(
                            'id' => $k,
                            'name' => $value,
                            'isCanonical' => $bIsCanonical
                        );
            }

            array_shift($arCategory);

            $categories[] = $arCategory;
        }

        return $categories;
    }
}