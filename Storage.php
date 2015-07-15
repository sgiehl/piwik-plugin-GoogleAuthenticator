<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAuthenticator;

use Piwik\Option;

class Storage
{
    /**
     * username to store data for
     * @var string
     */
    private $username = null;

    /**
     * Google Authenticator secret
     * @var string
     */
    private $secret = '';

    /**
     * Indicates if Google Authenticator is active for current user
     * @var bool
     */
    private $isActive = false;

    public function __construct($username)
    {
        $this->username = $username;
        $this->load();
    }

    /**
     * Set Google Authenticator secret
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        $this->save();
    }

    /**
     * Returns Google Authenticator secret
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Returns whether Google Authenticator is active
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Activates Google Authenticator for current user
     */
    public function activate()
    {
        $this->isActive = true;
        $this->save();
    }

    /**
     * Disables Google Authenticator for current user
     */
    public function deactivate()
    {
        $this->isActive = false;
        $this->save();
    }

    /**
     * Loads stored data
     */
    protected function load()
    {
        $data = Option::get('GoogleAuthentication.'.$this->username);
        if (!$data) {
            return;
        }

        $data = (array) @unserialize($data);

        if (isset($data['secret'])) {
            $this->secret = $data['secret'];
        }
        if (isset($data['isActive'])) {
            $this->isActive = $data['isActive'];
        }
    }

    /**
     * Saves data to storage
     */
    protected function save()
    {
        $data = array(
            'secret' => $this->secret,
            'isActive' => $this->isActive,
        );

        Option::set('GoogleAuthentication.'.$this->username, serialize($data));
    }


}
