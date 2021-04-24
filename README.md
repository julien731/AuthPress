Authenticator for WordPress
==================================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/julien731/WP-Google-Authenticator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/julien731/WP-Google-Authenticator/?branch=master)

If you are concerned about security, you should look into 2-factor authentication.

*Quick reminder:* 2-factor authentication adds an extra layer of security by requesting a one time password in addition to standard username / password credentials.

[Download the Google Authenticator app](https://support.google.com/accounts/answer/1066447?hl=en) on your phone (iPhone, Android or Blackberry). Install this plugin on your site. After activating it and generating a secret key, you will be able to add the site to your app by scanning a QR code. That's it!

The QR code is generated with Google Charts API using HTTPS to avoid security issues while sending your secret for generation.

What the plugin does:
---
- Adds 2-factor authentication to WordPress login page,
- Can be eanbled for each user independantly,
- Admin can force users to use 2FA (and limit the number of allowed logins without setting up 2FA). The use of 2FA can be forced for all users or for specific roles,
- Support applications passwords (with access log),
- If admin forces users to use 2FA, users who didn't set it up will be reminded with a warning in their dashboard,
- Set any name you want to appear in the Google Authenticator app,
- Allow clock discrepancy (mins +/-),
- Users can generate a new secret key anytime,
- Admin can revoke any user's key at anytime,
- If a user is locked-out after logging-in too many times without using 2FA, admin can reset the counter,
- Used one time passwords are hashed and stored in the DB to avoid multiple use (in case of interception by an attacker)
- Recovery code in case the user can't use the app

### Using Authy

You're using [Authy](https://www.authy.com/)? Authenticator for WordPress is fully compatible with Authy. You can add the 2-steps authentication and use Authy to generate the one time password.

## Changelog ##

### 1.1.1

* Fix an issue with the settings page not showing up
* Contextual help deprecated bug
* Remove mentions of Google in the plugin name chore

### 1.1.0
* Add support for apps passwords
* Admins can now force 2FA by user role
* Add Finnish translation (props [Makke375](https://wordpress.org/support/profile/makke375))
* Improve performance by reducing plugin footprint
* Fix the bug that allowed users to login 1 more time after they reached the limit when they don't have 2FA setup yet

### 1.0.7
* Add cron task to clean TOTPs from DB

### 1.0.6 ###
* Fix issue with spaces in site name (jeremyawhite)

### 1.0.5 ###
* Add issuer in the Google Authenticator account

### 1.0.4 ###
* Add recovery code feature
* Update translations

### 1.0.3 ###
* Add support for WordPress Android / iPhone app
* Add French translation

### 1.0.2 ###
* Update version number
* Remove double confirmation message after saving options
* Update option label and disable TOTP if plugin is not set to Active

### 1.0.1 ###
* Only push the trunk

### 1.0.0 ###
* First release of the plugin