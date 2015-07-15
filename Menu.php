<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAuthenticator;

use Piwik\Menu\MenuUser;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureUserMenu(MenuUser $menu)
    {
        if (!Piwik::isUserIsAnonymous()) {
            $menu->addPersonalItem('GoogleAuthenticator_Menu', $this->urlForAction('settings'), $order = 9);
        }

    }

}
