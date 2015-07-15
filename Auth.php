<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAuthenticator;

use Piwik\AuthResult;
use Piwik\Session\SessionNamespace;

/**
 * Extends Auth class of Login plugin to handle required auth codes
 */
class Auth extends \Piwik\Plugins\Login\Auth
{
    /**
     * Used as AuthResult to indicate missing auth code
     */
    const AUTH_CODE_REQUIRED = -42;

    /**
     * Auth-Code
     * @var string
     */
    protected $authCode = null;

    /**
     * Returns authentication module's name
     * @return string
     */
    public function getName()
    {
        return 'GoogleAuthenticator';
    }

    /**
     * Sets whether the current session is validated with auth code
     * @param bool|true $isValid
     */
    protected function setValidatedWithAuthCode($isValid = true)
    {
        $session = new SessionNamespace('GoogleAuthenticator');
        $session->validatedWithAuthCode = $isValid;
    }

    /**
     * Returns if the current session is validated with auth code
     * @return bool
     */
    protected function getValidateWithAuthCode()
    {
        $session = new SessionNamespace('GoogleAuthenticator');
        return (boolean) $session->validatedWithAuthCode;

    }

    /**
     * Returns if the user needs to authenticate with an auth code
     * @return bool
     */
    public function isAuthCodeRequired()
    {
        if (empty($this->login)) {
            return false;
        }

        $storage = new Storage($this->login);

        if ($storage->isActive() && !$this->getValidateWithAuthCode()) {
            return true;
        }

        return false;
    }

    /**
     * Returns if the set auth code is valid and updates the validation status of the current session
     * @return bool
     */
    public function validateAuthCode()
    {
        $storage = new Storage($this->getLogin());

        $secret = $storage->getSecret();
        $googleAuth = new PHPGangsta\GoogleAuthenticator();

        if (!empty($secret) && $googleAuth->verifyCode($secret, $this->authCode, 2)) {
            $this->setValidatedWithAuthCode(true);
            return true;
        }

        return false;
    }

    /**
     * Authenticates user
     *
     * @return AuthResult
     */
    public function authenticate()
    {
        $authResult = parent::authenticate();

        // if authentication was correct, check if an auth code is required
        if ($authResult->wasAuthenticationSuccessful()) {

            $storage = new Storage($authResult->getIdentity());

            // if Google Authenticator is disabled, or user already validated with auth code
            if (!$storage->isActive() || $this->getValidateWithAuthCode()) {
                return $authResult;
            }

            $authResult = new AuthResult(self::AUTH_CODE_REQUIRED, $this->login, $this->token_auth);
        }

        return $authResult;
    }

    /**
     * Accessor to set auth code
     *
     * @param string $authCode user's auth code
     */
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }

}