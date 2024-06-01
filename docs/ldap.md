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
composer config minimum-stability dev
composer config prefer-stable true

composer require rakoitde/shieldldap dev-develop
```

### Initial Setup

1. Run the following command. This command handles step 1-2 from *Manual Setup* and runs the migrations.

    ```console
    php spark shieldldap:setup
    ```

    Configure **app/Config/AuthLDAP.php** for your needs.

### Manual Setup

1. AuthLDAP.php

Copy the **AuthLDAP.php** from **vendor/rakoitde/shieldldap/src/Config/** into your project's config folder and update the namespace to `Config`. You will also need to have these classes extend the original classes. See the example below.

    ```php
    // new file - app/Config/AuthLDAP.php
    <?php

    declare(strict_types=1);

    namespace Rakoitde\Shieldldap\Config;

    use App\Config\Auth;
    use Rakoitde\Shieldldap\Config\AuthLDAP as ShieldAuthLDAP;

    /**
     * LDAP Authenticator Configuration
     */
    class AuthLDAP extends ShieldAuthLDAP
    {
    ```

Configure **app/Config/AuthLDAP.php** for your needs.

2. Auth.php

If your **app/Config/Auth.php** is not up-to-date, you also need to update it. Check **vendor/codeigniter4/shield/src/Config/Auth.php** and apply the differences.

    You need to add the following constants:
    ```php
    use Rakoitde\Shieldldap\Models\UserModel;
    use Rakoitde\Shieldldap\Authentication\Authenticators\LDAP;

    // ...

        public array $authenticators = [
            'tokens'  => AccessTokens::class,
            'session' => Session::class,
            'ldap'    => LDAP::class,
        ];

    // ...
        public string $defaultAuthenticator = 'ldap';

    // ...
        public bool $allowRegistration = false;

    // ...
        public bool $allowMagicLinkLogins = false;

    // ...
        public array $views = [
            'login'                       => '\Rakoitde\Shieldldap\Views\login',

    // ...

        public array $passwordValidators = [
            CompositionValidator::class,
            // NothingPersonalValidator::class,
            DictionaryValidator::class,
            // PwnedValidator::class,
        ];

    // ...

        public array $validFields = [
            'username'
        ];

    // ...

        public string $userProvider = \Rakoitde\Shieldldap\Models\UserModel::class;
    ```


## Protecting Routes

The first way to specify which routes are protected is to use the `AuthLDAP` controller
filter.

For example, to ensure it protects all routes, you
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


