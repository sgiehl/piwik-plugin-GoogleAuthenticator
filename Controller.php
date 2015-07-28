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
use Piwik\Auth as AuthInterface;
use Piwik\Container\StaticContainer;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugins\Login\PasswordResetter;
use Piwik\Url;
use Piwik\View;

/**
 * Google Authenticator Login controller
 */
class Controller extends \Piwik\Plugins\Login\Controller
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * Constructor.
     *
     * @param PasswordResetter $passwordResetter
     * @param AuthInterface $auth
     * @param SessionInitializer $sessionInitializer
     */
    public function __construct($passwordResetter = null, $auth = null, $sessionInitializer = null)
    {
        if (empty($auth)) {
            $auth = StaticContainer::get('Piwik\Auth');
        }
        $this->auth = $auth;

        if (empty($sessionInitializer)) {
            $sessionInitializer = new SessionInitializer();
        }

        if (empty($passwordResetter)) {
            $passwordResetter = new PasswordResetter(null, 'GoogleAuthenticator');
        }

        parent::__construct($passwordResetter, $auth, $sessionInitializer);
    }

    /**
     * Form to ask the users to authenticate with auth code
     * @param string $messageNoAccess
     * @return string
     * @throws \Exception
     */
    public function authcode($messageNoAccess = null)
    {
        $rememberMe = Common::getRequestVar('form_rememberme', '0', 'string') == '1';

        $form = new FormAuthCode();
        $form->removeAttribute('action'); // remove action attribute, otherwise hash part will be lost
        if ($form->validate()) {
            $nonce = $form->getSubmitValue('form_nonce');
            if (Nonce::verifyNonce('Login.login', $nonce)) {
                $this->auth->setAuthCode($form->getSubmitValue('form_authcode'));
                if ($this->auth->validateAuthCode()) {
                    try {
                        $this->authenticateAndRedirect($this->auth->getLogin(), null, $rememberMe);
                    } catch (\Exception $e) {
                    }
                }

                Nonce::discardNonce('Login.login');
                $form->getElements()[0]->setError(Piwik::translate('GoogleAuthenticator_AuthCodeInvalid'));
            } else {
                $messageNoAccess = $this->getMessageExceptionNoAccess();
            }
        }

        $view = new View('@GoogleAuthenticator/authcode');
        $view->logouturl = Url::getCurrentUrlWithoutQueryString() . '?' . Url::getQueryStringFromParameters(array(
                'module' => $this->auth->getName(),
                'action' => 'logout'
            ));
        $view->login = $this->auth->getLogin();
        $view->AccessErrorString = $messageNoAccess;
        $view->infoMessage = Piwik::translate('GoogleAuthenticator_AuthCodeRequired');
        $view->rememberMe = $rememberMe;
        $this->configureView($view);
        $view->addForm($form);
        self::setHostValidationVariablesView($view);

        return $view->render();
    }

    /**
     * Pretty the same as in login action of Login plugin
     * - Adds the handling for required auth code for login
     *
     * @param string $messageNoAccess Access error message
     * @param bool $infoMessage
     * @internal param string $currentUrl Current URL
     * @return string
     */
    function login($messageNoAccess = null, $infoMessage = false)
    {
        if ($this->auth->isAuthCodeRequired()) {
            return $this->authcode();
        }

        $form = new \Piwik\Plugins\Login\FormLogin();
        $form->removeAttribute('action'); // remove action attribute, otherwise hash part will be lost
        if ($form->validate()) {
            $nonce = $form->getSubmitValue('form_nonce');
            if (Nonce::verifyNonce('Login.login', $nonce)) {
                $login = $form->getSubmitValue('form_login');
                $password = $form->getSubmitValue('form_password');
                $rememberMe = $form->getSubmitValue('form_rememberme') == '1';
                try {
                    $this->authenticateAndRedirect($login, $password, $rememberMe);
                } catch (AuthCodeRequiredException $e) {
                    return $this->authcode();
                } catch (\Exception $e) {
                    $messageNoAccess = $e->getMessage();
                }
            } else {
                $messageNoAccess = $this->getMessageExceptionNoAccess();
            }
        }

        $view = new View('@Login/login');
        $view->AccessErrorString = $messageNoAccess;
        $view->infoMessage = nl2br($infoMessage);
        $view->addForm($form);
        $this->configureView($view);
        self::setHostValidationVariablesView($view);

        return $view->render();
    }


    /**
     * Configure common view properties
     *
     * @param View $view
     */
    private function configureView($view)
    {
        $this->setBasicVariablesView($view);

        $view->linkTitle = Piwik::getRandomTitle();

        // crsf token: don't trust the submitted value; generate/fetch it from session data
        $view->nonce = Nonce::getNonce('Login.login');
    }

    /**
     * Settings page for the user - allow activating / disabling Google Authenticator and to generate secrets
     *
     * @return string
     * @throws \Exception
     * @throws \Piwik\NoAccessException
     */
    public function settings()
    {
        Piwik::checkUserIsNotAnonymous();

        $view = new View('@GoogleAuthenticator/settings');
        $this->setGeneralVariablesView($view);

        $googleAuth = new PHPGangsta\GoogleAuthenticator();

        $storage = new Storage(Piwik::getCurrentUserLogin());

        $view->activated = $view->disabled = false;
        if (Common::getRequestVar('activate', 0, 'int')) {
            $storage->activate();
            $view->activated = true;
        }

        if (Common::getRequestVar('disable', 0, 'int')) {
            $storage->deactivate();
            $view->disabled = true;
        }

        $secret = $storage->getSecret();

        $view->showSetUp = Common::getRequestVar('setup', 0, 'int');
        $view->googleAuthIsActive = $storage->isActive();
        $view->googleAuthSecret = $secret;
        $view->googleAuthImage = $googleAuth->getQRCodeGoogleUrl(Piwik::getCurrentUserLogin(), $secret,
            'Piwik - ' . Url::getCurrentHost());

        return $view->render();
    }

    const AUTH_CODE_NONCE = 'saveAuthCode';

    /**
     * Action to generate a new Google Authenticator secret for the current user
     *
     * @return string
     * @throws \Exception
     * @throws \Piwik\NoAccessException
     */
    public function regenerate()
    {
        Piwik::checkUserIsNotAnonymous();

        $view = new View('@GoogleAuthenticator/regenerate');
        $this->setGeneralVariablesView($view);

        $googleAuth = new PHPGangsta\GoogleAuthenticator();

        $secret = Common::getRequestVar('secret', '', 'string');
        $authCode = Common::getRequestVar('authcode', '', 'string');
        $authCodeNonce = Common::getRequestVar('authCodeNonce', '', 'string');

        if (!empty($secret) && !empty($authCode) && Nonce::verifyNonce(self::AUTH_CODE_NONCE, $authCodeNonce) &&
            $googleAuth->verifyCode($secret, $authCode, 2)
        ) {
            $storage = new Storage(Piwik::getCurrentUserLogin());
            $storage->setSecret($secret);
            Url::redirectToUrl(Url::getCurrentUrlWithoutQueryString() . Url::getCurrentQueryStringWithParametersModified(array(
                    'action'   => 'settings',
                    'activate' => '1'
                )));
        }

        if (empty($secret)) {
            $secret = $googleAuth->createSecret(32);
        }

        $view->authCodeNonce = Nonce::getNonce(self::AUTH_CODE_NONCE);
        $view->newSecret = $secret;
        $view->googleAuthImage = $googleAuth->getQRCodeGoogleUrl(Piwik::getCurrentUserLogin(), $secret,
            'Piwik - ' . Url::getCurrentHost());

        return $view->render();
    }

}
