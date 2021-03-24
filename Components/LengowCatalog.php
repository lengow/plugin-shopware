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
 * @subpackage  Components
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Models\Shop\Shop as ShopModel;
use Shopware_Plugins_Backend_Lengow_Bootstrap as LengowBootstrap;
use Shopware_Plugins_Backend_Lengow_Components_LengowConnector as LengowConnector;
use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowMain as LengowMain;

/**
 * Lengow catalog Class
 */
class Shopware_Plugins_Backend_Lengow_Components_LengowCatalog
{
    /**
     * Check if the account has catalogs not linked to a cms
     *
     * @return boolean
     */
    public static function hasCatalogNotLinked()
    {
        $lengowCatalogs = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_CMS_CATALOG);
        if (!$lengowCatalogs) {
            return false;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return array
     */
    public static function getCatalogList()
    {
        $catalogList = array();
        $lengowCatalogs = LengowConnector::queryApi(LengowConnector::GET, LengowConnector::API_CMS_CATALOG);
        if (!$lengowCatalogs) {
            return $catalogList;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            if ($catalog->name !== null) {
                $name = $catalog->name;
            } else {
                $name = LengowMain::decodeLogMessage(
                    'lengow_log/connection/catalog',
                    null,
                    array('catalog_id' => $catalog->id)
                );
            }
            $status = $catalog->is_active
                ? LengowMain::decodeLogMessage('lengow_log/connection/status_active')
                : LengowMain::decodeLogMessage('lengow_log/connection/status_draft');
            $label = LengowMain::decodeLogMessage(
                'lengow_log/connection/catalog_label',
                null,
                array(
                    'catalog_id' => $catalog->id,
                    'catalog_name' => $name,
                    'nb_products' => $catalog->products ? $catalog->products : 0,
                    'catalog_status' => $status,
                )
            );
            $catalogList[] = array(
                'label' => $label,
                'value' => $catalog->id,
            );
        }
        return $catalogList;
    }

    /**
     * Link all catalogs by API
     *
     * @param array $catalogsByShops all catalog ids organised by shops
     *
     * @return boolean
     */
    public static function linkCatalogs(array $catalogsByShops)
    {
        $catalogsLinked = false;
        $hasCatalogToLink = false;
        if (empty($catalogsByShops)) {
            return $catalogsLinked;
        }
        $linkCatalogData = array(
            'cms_token' => LengowMain::getToken(),
            'shops' => array(),
        );
        foreach ($catalogsByShops as $shopId => $catalogIds) {
            if (empty($catalogIds)) {
                continue;
            }
            $hasCatalogToLink = true;
            $em = LengowBootstrap::getEntityManager();
            /** @var ShopModel $shop */
            $shop = $em->getReference('Shopware\Models\Shop\Shop', $shopId);
            $shopToken = LengowMain::getToken($shop);
            $linkCatalogData['shops'][] = array(
                'shop_token' => $shopToken,
                'catalogs_id' => $catalogIds,
            );
            LengowMain::log(
                LengowLog::CODE_CONNECTION,
                LengowMain::setLogMessage(
                    'log/connection/try_link_catalog',
                    array(
                        'catalog_ids' => implode(', ', $catalogIds),
                        'shop_token' => $shopToken,
                        'shop_id' => $shop->getId(),
                    )
                )
            );
        }
        if ($hasCatalogToLink) {
            $result = LengowConnector::queryApi(
                LengowConnector::POST,
                LengowConnector::API_CMS_MAPPING,
                array(),
                json_encode($linkCatalogData)
            );
            if (isset($result->cms_token)) {
                $catalogsLinked = true;
            }
        }
        return $catalogsLinked;
    }
}
