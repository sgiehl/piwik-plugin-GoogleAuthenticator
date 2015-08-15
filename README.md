# Piwik Google Authenticator Plugin

[![Build Status](https://travis-ci.org/sgiehl/piwik-plugin-GoogleAuthenticator.png?branch=master)](https://travis-ci.org/sgiehl/piwik-plugin-GoogleAuthenticator)
[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=sgiehl&url=https://github.com/sgiehl/piwik-plugin-GoogleAuthenticator&title=Piwik Plugin GoogleAuthenticator=&tags=github&category=software) 


## Description

Adds a userbased possibility to use Google Authenticator 2FA as additional login security.
Each use can enable/disable this feature in his account settings.

This Plugin is based on the original Piwik Login plugin and needs this one to be installed but not active.

ATTENTION: Activating Google Authenticator for an account, also requires an auth code for direct API requests with the users token auth. Use ```&auth_code={authcode}``` to do that.

### Requirements

[Piwik](https://github.com/piwik/piwik) 2.14.0 or higher is required.

### Features

- Userbased activation of Google Authenticator 2FA

## Changelog

- 0.1.0 Added possibility to define title and description for Google Authenticator app
- 0.0.4 fixes password reset link
- 0.0.3 small improvements
- 0.0.2 Added first translations
- 0.0.1 Initial release

## Support

Please direct any feedback to [stefan@piwik.org](mailto:stefan@piwik.org)

## Contribute

Feel free to create issues and pull requests.

## License

GPLv3 or later

The used library [PHPGangsta/GoogleAuthenticator](https://github.com/PHPGangsta/GoogleAuthenticator) is licensed under BSD

