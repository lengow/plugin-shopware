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
 * @subpackage  Controllers
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

/**
 * Backend Lengow Order Controller
 */
class Shopware_Controllers_Backend_LengowOrder extends Shopware_Controllers_Backend_Application implements \Shopware\Components\CSRFWhitelistAware
{
    /**
     * @var string
     */
    protected $model = 'Shopware\Models\Order\Order';

    /**
     * @var string
     */
    protected $alias = 'order';

    /**
     * Get lengow order detail in order detail page
     */
    public function getOrderDetailAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $data = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::getOrderDetailAction($orderId);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $data
            )
        );
    }

    /**
     * Send Order action
     */
    public function getCallActionAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $action = $this->Request()->getParam('actionName');
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::callAction($order,$action);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Cancel and re-import order action
     */
    public function cancelAndReImportAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::cancelAndReImportOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Synchronize Order action
     */
    public function synchronizeAction()
    {
        $orderId = $this->Request()->getParam('orderId');
        $order = Shopware()->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(array('id' => $orderId));
        $success = Shopware_Plugins_Backend_Lengow_Components_LengowOrder::synchronizeOrder($order);
        $this->View()->assign(
            array(
                'success' => true,
                'data' => $success
            )
        );
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return array
     */
    public function getWhitelistedCSRFActions()
    {
        return array(
            'getOrderDetail',
            'getCallAction',
            'cancelAndReImport',
            'synchronize'
        );
    }
}
