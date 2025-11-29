# CodeAuth PHP SDK
[![Latest Stable Version](https://poser.pugx.org/codeauth/codeauth-sdk/v/stable.svg)](https://packagist.org/packages/codeauth/codeauth-sdk)
[![Total Downloads](https://poser.pugx.org/codeauth/codeauth-sdk/downloads.svg)](https://packagist.org/packages/codeauth/codeauth-sdk)
[![License](https://poser.pugx.org/codeauth/codeauth-sdk/license.svg)](https://packagist.org/packages/codeauth/codeauth-sdk)

Offical CodeAuth SDK. For more info, check the docs on our [official website](https://docs.codeauth.com).

## Install
You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:

```
composer require codeauth/codeauth-sdk
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```
require_once 'vendor/autoload.php';
```

## Basic Usage

### Initialize CodeAuth SDK
```php
use CodeAuth\CodeAuth;
CodeAuth::Initialize("<your project API endpoint>", "<your project ID>");
```

### Signin / Email
Begins the sign in or register flow by sending the user a one time code via email.
```php
$result = CodeAuth::SignInEmail("<user email>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_email": print("bad_email"); break;
	case "code_request_interval_reached": print("code_request_interval_reached"); break;
	case "code_hourly_limit_reached": print("code_hourly_limit_reached"); break;
	case "email_provider_error": print("email_provider_error"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server
}
```

### Signin / Email Verify
Checks if the one time code matches in order to create a session token.
```php
$result = CodeAuth::SignInEmailVerify("<user email>", "<one time code>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_email": print("bad_email"); break;
	case "bad_code": print("bad_code"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server
}
print($result["session_token"]);
print($result["email"]);
print($result["expiration"]);
print($result["refresh_left"]);
```

### Signin / Social
Begins the sign in or register flow by allowing users to sign in through a social OAuth2 link.
```php
$result = CodeAuth::SignInSocial("<social_type>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_social_type": print("bad_social_type"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server
}
print($result["signin_url"]);
```

### Signin / Social Verify
This is the next step after the user signs in with their social account. This request checks the authorization code given by the social media company in order to create a session token.
```php
$result = CodeAuth::SignInSocialVerify("<social type>", "<authorization code>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_social_type": print("bad_social_type"); break;
	case "bad_authorization_code": print("bad_authorization_code"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server
}
print($result["session_token"]);
print($result["email"]);
print($result["expiration"]);
print($result["refresh_left"]);
```

### Session / Info
Gets the information associated with a session token.
```php
$result = CodeAuth::SessionInfo("<session_token>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_session_token": print("bad_session_token"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server
}
print($result["email"]);
print($result["expiration"]);
print($result["refresh_left"]);
```

### Session / Refresh
Create a new session token using existing session token.
```php
$result = CodeAuth::SessionRefresh("<session_token>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_session_token": print("bad_session_token"); break;
	case "out_of_refresh": print("out_of_refresh"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break;//sdk failed to connect to api server
}
print($result["session_token"]);
print($result["email"]);
print($result["expiration"]);
print($result["refresh_left"]);
```

### Session / Invalidate
Invalidate a session token. By doing so, the session token can no longer be used for any api call.
```php
$result = CodeAuth::SessionInvalidate("<session_token>", "<invalidate_type>");
switch ($result["error"])
{
	case "bad_json": print("bad_json"); break;
	case "project_not_found": print("project_not_found"); break;
	case "bad_ip_address": print("bad_ip_address"); break;
	case "rate_limit_reached": print("rate_limit_reached"); break;
	case "bad_session_token": print("bad_session_token"); break;
	case "bad_invalidate_type": print("bad_invalidate_type"); break;
	case "internal_error": print("internal_error"); break;
	case "connection_error": print("connection_error"); break; //sdk failed to connect to api server 
}
```

