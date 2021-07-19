<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * It is available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/agpl-3.0
 *
 * @category    Lengow
 * @package     Lengow
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Enlight_Event_EventArgs as EventArgs;
use Enlight_Controller_Action as ActionController;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap_Database as LengowBootstrapDatabase;
use Shopware_Plugins_Backend_Lengow_Components_LengowAction as LengowAction;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowOrder as LengowOrder;

/**
 * Lengow Event Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowEvent
{
    /**
     * @var array order changed for call action
     */
    public static $orderChanged = array();

    /**
     * @var array path for Lengow options
     */
    public static $lengowOptions = array(
        'lengow_main_settings',
        'lengow_export_settings',
        'lengow_import_settings',
        'lengow_order_status_settings',
    );

    /**
     * Listen to basic settings changes. Log of Lengow settings when they were updated
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPreDispatchBackendConfig($args)
    {
        $request = $args->getSubject()->Request();
        $data = $request->getPost();
        // if action is from Shopware basics settings plugin and editing shop form
        if ($request->getControllerName() === 'Config' && in_array($data['name'], self::$lengowOptions, true)) {
            $lengowSettings = LengowConfiguration::$lengowSettings;
            $elements = $data['elements'];
            foreach ($elements as $element) {
                $key = $element['name'];
                if (array_key_exists($key, $lengowSettings)){
                    $setting = $lengowSettings[$key];
                    if (isset($setting[LengowConfiguration::PARAM_SHOP]) && $setting[LengowConfiguration::PARAM_SHOP]) {
                        foreach ($element['values'] as $shopValues) {
                            LengowConfiguration::checkAndLog($key, $shopValues['value'], (int) $shopValues['shopId']);
                        }
                    } else {
                        $value = $element['values'][0]['value'];
                        LengowConfiguration::checkAndLog($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Listen to basic settings changes. Add/remove lengow column from s_articles_attributes
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     *
     * @return boolean
     */
    public static function onPostDispatchBackendConfig($args)
    {
        $request = $args->getSubject()->Request();
        $controllerName = $request->getControllerName();
        // since 5.x, forms use _repositoryClass parameter to specify the repository to update
        if (LengowMain::compareVersion('5.0.0')) {
            $repositoryName = $request->get('_repositoryClass');
        } else {
            $repositoryName = $request->get('name');
        }
        // if action is from Shopware basics settings plugin and editing shop form
        if ($controllerName === 'Config' && $repositoryName === 'shop') {
            $action = $request->getActionName();
            $lengowDatabase = new LengowBootstrapDatabase();
            $data = $request->getPost();
            // if new shop, get last entity put in db
            try {
                if ($action === 'saveValues') {
                    /** @var ShopModel $shop */
                    $shop = Shopware()->Models()
                        ->getRepository('Shopware\Models\Shop\Shop')
                        ->findOneBy(array(), array('id' => 'DESC'));
                    $lengowDatabase->addLengowColumns(array($shop->getId()));
                } elseif ($action === 'deleteValues') {
                    $shopId = isset($data['id']) ? $data['id'] : null;
                    if (!empty($shopId)) {
                        $lengowDatabase->removeLengowColumn(array($shopId));
                    }
                }
            } catch (Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Listen to order changes before save
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPreDispatchBackendOrder($args)
    {
        $request = $args->getSubject()->Request();
        if ($request->getActionName() === 'save') {
            $data = $request->getPost();
            if (LengowOrder::isFromLengow($data['id'])) {
                /** @var OrderModel $order */
                $order = Shopware()->Models()
                    ->getRepository('Shopware\Models\Order\Order')
                    ->findOneBy(array('id' => $data['id']));
                if ($order->getTrackingCode() !== $data['trackingCode']
                    || $order->getOrderStatus()->getId() !== (int) $data['status']
                ) {
                    self::$orderChanged[$order->getId()] = true;
                }
            }
        }
    }

    /**
     * Listen to order changes after save / send call action if necessary
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPostDispatchBackendOrder($args)
    {
        $request = $args->getSubject()->Request();

        if ($request->getActionName() === 'save') {
            $data = $request->getPost();
            if (array_key_exists($data['id'], self::$orderChanged) && LengowOrder::isFromLengow($data['id'])) {
                /** @var OrderModel $order */
                $order = Shopware()->Models()
                    ->getRepository('Shopware\Models\Order\Order')
                    ->findOneBy(array('id' => $data['id']));
                // call Lengow API WSDL to send ship or cancel actions
                $shippedStatus = LengowMain::getOrderStatus(LengowOrder::STATE_SHIPPED);
                $canceledStatus = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
                if ($order->getOrderStatus()->getId() === $shippedStatus->getId()) {
                    LengowOrder::callAction($order, LengowAction::TYPE_SHIP);
                } elseif ($order->getOrderStatus()->getId() === $canceledStatus->getId()) {
                    LengowOrder::callAction($order, LengowAction::TYPE_CANCEL);
                }
                unset(self::$orderChanged[$data['id']]);
            }
        }
    }

    /**
     * Listen to api order changes after save / send call action if necessary
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onApiOrderPostDispatch($args)
    {
        $request = $args->getSubject()->Request();
        if ($request->getActionName() === 'put') {
            $orderId = $request->getParam('id');
            $useNumberAsId = (bool) $request->getParam('useNumberAsId', 0);
            if ($useNumberAsId) {
                $orderId = LengowOrder::getOrderIdByNumber($orderId);
            }
            if ($orderId && LengowOrder::isFromLengow((int) $orderId)) {
                /** @var OrderModel $order */
                $order = Shopware()->Models()
                    ->getRepository('Shopware\Models\Order\Order')
                    ->findOneBy(array('id' => $orderId));
                // call Lengow API WSDL to send ship or cancel actions
                $shippedStatus = LengowMain::getOrderStatus(LengowOrder::STATE_SHIPPED);
                $canceledStatus = LengowMain::getOrderStatus(LengowOrder::STATE_CANCELED);
                if ($order->getOrderStatus()->getId() === $shippedStatus->getId()) {
                    LengowOrder::callAction($order, LengowAction::TYPE_SHIP);
                } elseif ($order->getOrderStatus()->getId() === $canceledStatus->getId()) {
                    LengowOrder::callAction($order, LengowAction::TYPE_CANCEL);
                }
            }
        }
    }

    /**
     * Adding simple tracker Lengow on footer when order is confirmed
     *
     * @param EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onFrontendCheckoutPostDispatch($args)
    {
        $request = $args->getSubject()->Request();
        if ($request->getActionName() === 'finish'
            && (bool) LengowConfiguration::getConfig(LengowConfiguration::TRACKING_ENABLED)
        ) {
            $sOrderVariables = Shopware()->Session()->offsetGet('sOrderVariables')->getArrayCopy();
            // get all tracker variables
            $accountId = LengowConfiguration::getConfig(LengowConfiguration::ACCOUNT_ID);
            $trackingId = LengowConfiguration::getConfig(LengowConfiguration::TRACKING_ID);
            if (!empty($sOrderVariables) && $accountId > 0) {
                // get all tracker variables
                $payment = isset($sOrderVariables['sPayment']) ? $sOrderVariables['sPayment'] : '';
                $articleCart = array();
                $articles = isset($sOrderVariables['sBasket']['content'])
                    ? $sOrderVariables['sBasket']['content']
                    : array();
                foreach ($articles as $article) {
                    $articleCart[] = array(
                        'product_id' => $trackingId === 'id' ? (int) $article['id'] : $article['ordernumber'],
                        'price' => (float) $article['price'],
                        'quantity' => (int) $article['quantity'],
                    );
                }
                // assign all tracker variables in page
                /** @var ActionController $controller */
                $controller = $args->getSubject();
                $view = $controller->View();
                $view->assign(
                    'lengowVariables',
                    array(
                        'account_id' => $accountId,
                        'order_ref' => isset($sOrderVariables['sOrderNumber'])? $sOrderVariables['sOrderNumber'] : '',
                        'amount' => isset($sOrderVariables['sAmount']) ? $sOrderVariables['sAmount'] : '',
                        'currency' => Shopware()->Shop()->getCurrency()->getCurrency(),
                        'payment_method' => isset($payment['name']) ? $payment['name'] : '',
                        'cart' => json_encode($articleCart),
                        'cart_number' => 0,
                        'newbiz' => 1,
                        'valid' => 1,
                    )
                );
                // generate tracker template in footer
                $view->addTemplateDir(__DIR__ . '/../Views');
                $view->extendsTemplate('frontend/lengow/tracker.tpl');
            }
        }
    }
}
