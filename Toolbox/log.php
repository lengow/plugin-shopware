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

require 'views/header.php';

$listFiles = array_reverse(LengowLog::getPaths());
?>
    <div class="container">
        <h1><?php echo $locale->t('toolbox/log/log_files'); ?></h1>
        <ul class="list-group">
            <?php
            foreach ($listFiles as $file) {
                echo '<li class="list-group-item">';
                echo '<a href="' . $file['link'] . '" target="_blank"><i class="fa fa-download"></i> '
                    . $file['name'] . '</a>';
                echo '</li>';
            }
            ?>
        </ul>
    </div><!-- /.container -->
<?php
require 'views/footer.php';
