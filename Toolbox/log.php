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

use Shopware_Plugins_Backend_Lengow_Components_LengowLog as LengowLog;
use Shopware_Plugins_Backend_Lengow_Components_LengowToolbox as LengowToolbox;

require 'views/header.php';

$listFile = LengowToolbox::getData(LengowToolbox::DATA_TYPE_LOG);
?>
    <div class="container">
        <h1><?php echo $locale->t('toolbox/log/log_files'); ?></h1>
        <ul class="list-group">
            <?php
            foreach ($listFile as $file) {
                $name = $file[LengowLog::LOG_DATE]
                    ? date('l d F Y', strtotime($file[LengowLog::LOG_DATE]))
                    : $locale->t('toolbox/log/download_all');
                echo '<li class="list-group-item"><a href="' . $file[LengowLog::LOG_LINK] . '">' . $name . '</a></li>';
            }
            ?>
        </ul>
    </div><!-- /.container -->
<?php
require 'views/footer.php';
