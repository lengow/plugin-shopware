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
 * @subpackage  Toolbox
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     https://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License, version 3
 */

use Shopware\Kernel;
use Shopware\Components\HttpCache\AppCache;

$toolboxPath = 'engine/Shopware/Plugins/Community/Backend/Lengow/Toolbox/';
$currentDirectory = str_replace($toolboxPath, '', dirname($_SERVER['SCRIPT_FILENAME'])."/");

require_once $currentDirectory.'autoload.php';

require_once('../Bootstrap.php');
require_once('../Components/LengowTranslation.php');
require_once('../Components/LengowMain.php');
require_once('../Components/LengowConfiguration.php');
require_once('../Components/LengowCheck.php');
require_once('../Components/LengowFile.php');
require_once('../Components/LengowLog.php');

$environment = getenv('ENV') ?: getenv('REDIRECT_ENV') ?: 'production';

$kernel = new Kernel($environment, $environment !== 'production');
$kernel->boot();
if ($kernel->isHttpCacheEnabled()) {
    $kernel = new AppCache($kernel, $kernel->getHttpCacheConfig());
}
