# LDAP Authentication

## Setup

To use LDAP Authentication, you need additional setup and configuration.

### Composer Installation

1. Install CodeIgniter Shield

Install CodeIgniter Shield. See [Installation](https://codeigniter4.github.io/shield/install/)

If you don't run shield:setup in installation process, the shieldldap:setup don't set correct routes
    ```console
    php spark shield:setup
    ```

2. Install ShieldLdap

Install "rakoitde/shieldldap" via Composer.

    ```console
    composer require rakoitde/shieldldap
    ```

### Initial Setup

1. Run the following command. This command handles steps 1-5 of *Manual Setup* and runs the migrations.

    ```console
    php spark shieldldap:setup
    ```

Copy the **AuthLDAP.php** from **vendor/rakoitde/shieldldap/src/Config/** into your project's config folder and update the namespace to `Config`. You will also need to have these classes extend the original classes. See the example below.

    ```php
    // new file - app/Config/AuthLDAP.php
    <?php

    declare(strict_types=1);

    namespace Config;

    use CodeIgniter\Shield\Config\AuthLDAP as ShieldAuthLDAP;

    /**
     * JWT Authenticator Configuration
     */
    class AuthJWT extends ShieldAuthLDAP
    {
        // ...
    }
    ```

3. If your **app/Config/Auth.php** is not up-to-date, you also need to update it. Check **vendor/codeigniter4/shield/src/Config/Auth.php** and apply the differences.

    You need to add the following constants:
    ```php
        public const RECORD_LOGIN_ATTEMPT_NONE    = 0; // Do not record at all
        public const RECORD_LOGIN_ATTEMPT_FAILURE = 1; // Record only failures
        public const RECORD_LOGIN_ATTEMPT_ALL     = 2; // Record all login attempts
    ```
    ```php
    public array $views = [
        'login'                       => '\Rakoitde\Shieldldap\Views\login',
        'register'                    => '\Rakoitde\Shieldldap\Views\register',
        //'layout'                      => '\CodeIgniter\Shield\Views\layout',
        //'action_email_2fa'            => '\CodeIgniter\Shield\Views\email_2fa_show',
        //'action_email_2fa_verify'     => '\CodeIgniter\Shield\Views\email_2fa_verify',
        //'action_email_2fa_email'      => '\CodeIgniter\Shield\Views\Email\email_2fa_email',
        //'action_email_activate_show'  => '\CodeIgniter\Shield\Views\email_activate_show',
        //'action_email_activate_email' => '\CodeIgniter\Shield\Views\Email\email_activate_email',
        'magic-link-login'            => '\CodeIgniter\Shield\Views\magic_link_form',
        'magic-link-message'          => '\CodeIgniter\Shield\Views\magic_link_message',
        'magic-link-email'            => '\CodeIgniter\Shield\Views\Email\magic_link_email',
    ];
    ```

    You need to add LDAP Authenticator:
    ```php
    use Rakoitde\Shieldldap\Authentication\Authenticators\LDAP;

    // ...

        public array $authenticators = [
            'tokens'  => AccessTokens::class,
            'session' => Session::class,
            'ldap'     => LDAP::class,
        ];
    ```

    If you want to use LDAP Authenticator in Authentication Chain, add `ldap`:
    ```php
        public array $authenticationChain = [
            'session',
            'tokens',
            'ldap'
        ];
    ```

    Remove 'NothingPersonalValidator::Class' in $passwordValidators

    ```php
    public array $passwordValidators = [
        CompositionValidator::class,
        //NothingPersonalValidator::class,
        DictionaryValidator::class,
        // PwnedValidator::class,
    ];
    ```

    Add 'username' to $validFields

    ```php
    public array $validFields = [
        // 'email',
        'username',
    ];
    ```

## Configuration

Configure **app/Config/AuthLDAP.php** for your needs.

## Extend available authenticators

```php
    use Rakoitde\Shieldldap\Authentication\Authenticators\LDAP;

    //...

    public array $authenticators = [
        'tokens'  => AccessTokens::class,
        'session' => Session::class,
        'ldap'    => LDAP::class,
    ];

    //...

    public string $defaultAuthenticator = 'ldap';
```

## Protecting Routes

The first way to specify which routes are protected is to use the `jwt` controller
filter.

For example, to ensure it protects all routes under the `/api` route group, you
would use the `$globals` setting on **app/Config/Filters.php**.

```php
public array $globals = [
    'before' => [
        'session' => ['except' => ['login*', 'register']],
    ],
```


You can also specify the filter should run on one or more routes within the routes
file itself:

```php
//service('auth')->routes($routes);
service('auth')->routes($routes, ['except' => ['login']]);
$routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView');
$routes->post('login', '\Rakoitde\Shieldldap\Controllers\Auth\LoginController::ldapLogin');
```

When the filter runs, it checks the `Authorization` header for a `Bearer` value
that has the JWT. It then validates the token. If the token is valid, it can
determine the correct user, which will then be available through an `auth()->user()`
call.

