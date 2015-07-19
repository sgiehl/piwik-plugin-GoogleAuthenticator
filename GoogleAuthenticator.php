<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAuthenticator;

use Piwik\Common;
use Piwik\Container\StaticContainer;

/**
 *
 */
class GoogleAuthenticator extends \Piwik\Plugins\Login\Login
{
    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        $hooks = array(
            'API.Request.authenticate'         => 'ApiRequestAuthenticate',
        );
        return $hooks;
    }

    /**
     * Set login name and authentication token for API request.
     * Listens to API.Request.authenticate hook.
     */
    public function ApiRequestAuthenticate($tokenAuth)
    {
        /** @var \Piwik\Auth $auth */
        $auth = StaticContainer::get('Piwik\Auth');
        $auth->setLogin($login = null);
        $auth->setTokenAuth($tokenAuth);
        $auth->setAuthCode(Common::getRequestVar('auth_code', '', 'string'));
    }
}
