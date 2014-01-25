Google Authenticator for WordPress
==================================

If you are concerned about security, you should look into 2-factor authentication.

*Quick reminder:* 2-factor authentication adds an extra layer of security by requesting a one time password in addition to standard username / password credentials.

This plugin uses the Google Authenticator app. I bet you know Google, and you probably know they have some good products out there. Google Authenticator is one of them.

[Download the Google Authenticator app](https://support.google.com/accounts/answer/1066447?hl=en) on your phone (iPhone, Android or Blackberry). Install this plugin on your site. After activating it and generating a secret key, you will be able to add the site to your app by scanning a QR code. That's it!

The QR code is generated with Google Charts API using HTTPS to avoid security issues while sending your secret for generation.

What the plugin does:
---
- Adds 2-factor authentication to WordPress login page,
- Can be eanbled for each user independantly,
- Admin can force users to use 2FA (and limit the number of allowed logins without setting up 2FA),
- If admin forces users to use 2FA, users who didn't set it up will be reminded with a warning in their dashboard,
- Set any name you want to appear in the Google Authenticator app,
- Allow clock discrepancy (mins +/-),
- Users can generate a new secret key anytime,
- Admin can revoke any user's key at anytime,
- If a user is locked-out after logging-in too many times without using 2FA, admin can reset the counter,
- Used one time passwords are hashed and stored in the DB to avoid multiple use (in case of interception by an attacker)
- Recovery code in case the user can't use the app

## Changelog ##

### v1.0.4 ###
* Add recovery code feature
* Update translations

### v1.0.3 ###
* Add support for WordPress Android / iPhone app
* Add French translation

### v1.0.2 ###
* Update version number
* Remove double confirmation message after saving options
* Update option label and disable TOTP if plugin is not set to Active

### v1.0.1 ###
* Only push the trunk

### v1.0.0 ###
* First release of the plugin