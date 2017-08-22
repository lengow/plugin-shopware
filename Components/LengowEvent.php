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
     * Listen to basic settings changes. Add/remove lengow column from s_articles_attributes
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPostDispatchBackendConfig($args)
    {
        $request = $args->getSubject()->Request();
        $controllerName = $request->getControllerName();
        // Since 5.x, forms use _repositoryClass parameter to specify the repository to update
        if (Shopware_Plugins_Backend_Lengow_Components_LengowMain::compareVersion('5.0.0')) {
            $repositoryName = $request->get('_repositoryClass');
        } else {
            $repositoryName = $request->get('name');
        }
        // If action is from Shopware basics settings plugin and editing shop form
        if ($controllerName == 'Config' && $repositoryName == 'shop') {
            $action = $request->getActionName();
            $lengowDatabase = new Shopware_Plugins_Backend_Lengow_Bootstrap_Database();
            $data = $request->getPost();
            // If new shop, get last entity put in db
            if ($action == 'saveValues') {
                $shop = Shopware()->Models()
                    ->getRepository('Shopware\Models\Shop\Shop')
                    ->findOneBy(array(), array('id' => 'DESC'));
                $lengowDatabase->addLengowColumns(array($shop->getId()));
            } elseif ($action == 'deleteValues') {
                $shopId = isset($data['id']) ? $data['id'] : null;
                if (!empty($shopId)) {
                    $lengowDatabase->removeLengowColumn(array($shopId));
                }
            }
        }
    }

    /**
     * Listen to order changes before save
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPreDispatchBackendOrder($args)
    {
        $request = $args->getSubject()->Request();
        if ($request->getActionName() === 'save') {
            $data = $request->getPost();
            if (Shopware_Plugins_Backend_Lengow_Components_LengowOrder::isFromLengow($data['id']) == 1) {
                $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                    ->findOneBy(array('id' => $data['id']));
                if ($order->getTrackingCode() != $data['trackingCode']
                    || $order->getOrderStatus()->getId() != $data['status']
                ) {
                    self::$orderChanged[$order->getId()] = true;
                }
            }
        }
    }

    /**
     * Listen to order changes after save / send call action if necessary
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onPostDispatchBackendOrder($args)
    {
        $request = $args->getSubject()->Request();

        if ($request->getActionName() === 'save') {
            $data = $request->getPost();
            if (Shopware_Plugins_Backend_Lengow_Components_LengowOrder::isFromLengow($data['id'])
                && array_key_exists($data['id'], self::$orderChanged)
            ) {
                $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')
                    ->findOneBy(array('id' => $data['id']));
                // Call Lengow API WSDL to send ship or cancel actions
                $shippedStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus('shipped');
                $canceledStatus = Shopware_Plugins_Backend_Lengow_Components_LengowMain::getOrderStatus('canceled');
                if ($order->getOrderStatus() == $shippedStatus) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowOrder::callAction($order, 'ship');
                } elseif ($order->getOrderStatus() == $canceledStatus) {
                    Shopware_Plugins_Backend_Lengow_Components_LengowOrder::callAction($order, 'cancel');
                }
                unset(self::$orderChanged[$data['id']]);
            }
        }
    }

    /**
     * Listen to order details
     *
     * @param Enlight_Event_EventArgs $args Shopware Enlight Controller Action instance
     */
    public static function onOrderPostDispatch($args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir(__DIR__ . '/../Views');

        if ($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/lengow/controller/order.js');
            $view->extendsTemplate('backend/lengow/order.js');
        }

        if ($request->getActionName() === 'load') {
            $view->extendsTemplate('backend/lengow/controller/order.js');
            $view->extendsTemplate('backend/lengow/order.js');
        }
    }
}
