<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAuthenticator;

use Exception;
use Piwik\Auth as AuthInterface;
use Piwik\AuthResult;
use Piwik\Piwik;
use Piwik\ProxyHttp;

/**
 * Extends SessionInitializer from Login plugin to handle the case that an auth code is required for login
 */
class SessionInitializer extends \Piwik\Plugins\Login\SessionInitializer
{
    /**
     * Authenticates the user and, if successful, initializes an authenticated session.
     *
     * @param \Piwik\Auth $auth The Auth implementation to use.
     * @param bool $rememberMe Whether the authenticated session should be remembered after
     *                         the browser is closed or not.
     * @throws AuthCodeRequiredException If authentication was successful but an google auth code is required to proceed
     * @throws Exception If authentication fails or the user is not allowed to login for some reason.
     */
    public function initSession(AuthInterface $auth, $rememberMe)
    {
        $this->regenerateSessionId();

        $authResult = $this->doAuthenticateSession($auth);

        if (!$authResult->wasAuthenticationSuccessful()) {
            if ($authResult->getCode() === Auth::AUTH_CODE_REQUIRED) {
                // Authenticate user with cookie, but throw exception as auth code is still required
                $this->processSuccessfulSession($authResult, $rememberMe);
                throw new AuthCodeRequiredException();
            }

            $this->processFailedSession($rememberMe);
        } else {
            $this->processSuccessfulSession($authResult, $rememberMe);
        }

        /**
         * @deprecated Create a custom SessionInitializer instead.
         */
        Piwik::postEvent('Login.initSession.end');
    }

    /**
     * Executed when the session was successfully authenticated.
     *
     * @param AuthResult $authResult The successful authentication result.
     * @param bool $rememberMe Whether the authenticated session should be remembered after
     *                         the browser is closed or not.
     */
    protected function processSuccessfulSession(AuthResult $authResult, $rememberMe)
    {
        $storage = new Storage($authResult->getIdentity());

        /**
         * @deprecated Create a custom SessionInitializer instead.
         */
        Piwik::postEvent(
            'Login.authenticate.successful',
            array(
                $authResult->getIdentity(),
                $authResult->getTokenAuth()
            )
        );

        $cookie = $this->getAuthCookie($rememberMe);
        $cookie->set('login', $authResult->getIdentity());
        $cookie->set('token_auth', $this->getHashTokenAuth($authResult->getIdentity(), $authResult->getTokenAuth()));
        if ($storage->isActive()) {
            $cookie->set('auth_code', $this->getHashTokenAuth($authResult->getIdentity(), $storage->getSecret()));
        }
        $cookie->setSecure(ProxyHttp::isHttps());
        $cookie->setHttpOnly(true);
        $cookie->save();
    }
}
