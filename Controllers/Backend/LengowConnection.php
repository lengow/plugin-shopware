<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowCatalog as LengowCatalog;
use Shopware_Plugins_Backend_Lengow_Components_LengowConfiguration as LengowConfiguration;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowElements as LengowElements;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;
use Shopware_Plugins_Backend_Lengow_Components_LengowSync as LengowSync;

/**
 * Backend Lengow Connection Controller
 */
class Shopware_Controllers_Backend_LengowConnection extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Go to credentials action
     */
    public function goToCredentialsAction()
    {
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'html' => LengowElements::getConnectionCms(),
                ),
            )
        );
    }

    /**
     * Connect cms action
     */
    public function connectCmsAction()
    {
        $cmsConnected = false;
        $hasCatalogToLink = false;
        $accessToken = $this->Request()->getParam('accessToken');
        $secret = $this->Request()->getParam('secret');
        $credentialsValid = $this->checkApiCredentials($accessToken, $secret);
        if ($credentialsValid) {
            $cmsConnected = $this->connectCms();
            if ($cmsConnected) {
                $hasCatalogToLink = $this->hasCatalogToLink();
            }
        }
        // get cms result template
        if ($hasCatalogToLink) {
            $html = LengowElements::getConnectionCmsSuccessWithCatalog();
        } elseif ($cmsConnected) {
            $html = LengowElements::getConnectionCmsSuccess();
        } else {
            $html = LengowElements::getConnectionCmsFailed($credentialsValid);
        }
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'cmsConnected' => $cmsConnected,
                    'hasCatalogToLink' => $hasCatalogToLink,
                    'html' => $html,
                ),
            )
        );
    }

    /**
     * Go to catalog action
     */
    public function goToCatalogAction()
    {
        $retry = $this->Request()->getParam('retry') !== 'false';
        if ($retry) {
            LengowConfiguration::resetCatalogIds();
        }
        $catalogList = $this->getCatalogList();
        $this->View()->assign(
            array(
                'success' => true,
                'data' => array(
                    'html' => LengowElements::getConnectionCatalog($catalogList),
                ),
            )
        );
    }

    /**
     * Link catalog action
     */
    public function linkCatalogAction()
    {
        $catalogsLinked = true;
        $catalogSelected = $this->Request()->getParam('catalogSelected')
            ? json_decode($this->Request()->getParam('catalogSelected'), true)
            : array();
        if (!empty($catalogSelected)) {
            $catalogsLinked = $this->saveCatalogsLinked($catalogSelected);
        }
        $this->View()->assign(
            array(
                'success' => $catalogsLinked,
                'data' => array(
                    'html' => LengowElements::getConnectionCatalogFailed(),
                ),
            )
        );
    }

    /**
     * Check API credentials and save them in Database
     *
     * @param string $accessToken access token for api
     * @param string $secret secret for api
     *
     * @return boolean
     */
    private function checkApiCredentials($accessToken, $secret)
    {
        $accessIdsSaved = false;
        $accountId = LengowConnector::getAccountIdByCredentials($accessToken, $secret);
        if ($accountId) {
            $accessIdsSaved = LengowConfiguration::setAccessIds(
                array(
                    LengowConfiguration::ACCOUNT_ID => $accountId,
                    LengowConfiguration::ACCESS_TOKEN => $accessToken,
                    LengowConfiguration::SECRET => $secret,
                )
            );
        }
        return $accessIdsSaved;
    }

    /**
     * Connect cms with Lengow
     *
     * @return boolean
     */
    private function connectCms()
    {
        $cmsToken = LengowMain::getToken();
        $cmsConnected = LengowSync::syncCatalog(true);
        if (!$cmsConnected) {
            $syncData = json_encode(LengowSync::getSyncData());
            $result = LengowConnector::queryApi(LengowConnector::POST, LengowConnector::API_CMS, array(), $syncData);
            if (isset($result->common_account)) {
                $cmsConnected = true;
                $messageKey = 'log/connection/cms_creation_success';
            } else {
                $messageKey = 'log/connection/cms_creation_failed';
            }
        } else {
            $messageKey = 'log/connection/cms_already_exist';
        }
        LengowMain::log(
            LengowLog::CODE_CONNECTION,
            LengowMain::setLogMessage(
                $messageKey,
                array('cms_token' => $cmsToken)
            )
        );
        // reset access ids if cms creation failed
        if (!$cmsConnected) {
            LengowConfiguration::resetAccessIds();
            LengowConfiguration::resetAuthorizationToken();
        }
        return $cmsConnected;
    }

    /**
     * Check if account has catalog to link
     *
     * @return boolean
     */
    private function hasCatalogToLink()
    {
        $activeShops = LengowMain::getLengowActiveShops();
        if (empty($activeShops)) {
            return LengowCatalog::hasCatalogNotLinked();
        }
        return false;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return array
     */
    private function getCatalogList()
    {
        $activeShops = LengowMain::getLengowActiveShops();
        if (empty($activeShops)) {
            return LengowCatalog::getCatalogList();
        }
        // if cms already has one or more linked catalogs, nothing is done
        return array();
    }

    /**
     * Save catalogs linked in database and send data to Lengow with call API
     *
     * @param array $catalogSelected catalog ids organized by shops
     *
     * @return boolean
     */
    private function saveCatalogsLinked($catalogSelected)
    {
        $catalogsLinked = true;
        $catalogsByShops = array();
        foreach ($catalogSelected as $catalog) {
            $catalogsByShops[$catalog['shopId']] = $catalog['catalogId'];
        }
        if (!empty($catalogsByShops)) {
            // save catalogs ids and active shop in lengow configuration
            foreach ($catalogsByShops as $shopId => $catalogIds) {
                $em = LengowBootstrap::getEntityManager();
                /** @var ShopModel $shop */
                $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
                LengowConfiguration::setCatalogIds($catalogIds, $shop);
                LengowConfiguration::setActiveShop($shop);
            }
            // save last update date for a specific settings (change synchronisation interval time)
            LengowConfiguration::setConfig('lengowLastSettingUpdate', time());
            // link all catalogs selected by API
            $catalogsLinked = LengowCatalog::linkCatalogs($catalogsByShops);
            $messageKey = $catalogsLinked
                ? 'log/connection/link_catalog_success'
                : 'log/connection/link_catalog_failed';
            LengowMain::log(LengowLog::CODE_CONNECTION, LengowMain::setLogMessage($messageKey));
        }
        return $catalogsLinked;
    }
}
