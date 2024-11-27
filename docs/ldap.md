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

    It is recommended that the AuthLdap settings are made in the .env to prevent internal company information from becoming public

    ```console
    #--------------------------------------------------------------------
    # Shield AuthLdap
    #--------------------------------------------------------------------

    authldap.ldap_host              = 
    authldap.ldap_domain            = 
    authldap.search_base            = 
    authldap.storePasswordInSession = false
    authldap.use_ldaps              = true
    authldap.username               = 
    authldap.password               = 
    ```

2. Check your config

    ```console
    php spark shieldldap:check

    Username : ldaptest
    password : ********
    AuthLDAP config
      ldap_host:              ldap.your-domain.local
      ldap_port:              389
      ldaps_port:             636
      use_ldaps:              true
      ldap_domain:            your-domain
      ldap_format_username:   DLN - Down-Level Logon Name (your-domain\username)
      search_base:            dc=your-domain,dc=local
      storePasswordInSession: true
      attributes:             objectSID, distinguishedname, displayName, title, description, cn, givenName, sn, mail, co, telephoneNumber, mobile, company, department, l, postalCode, streetAddress, displayName, samaccountname, thumbnailPhoto, userAccountControl

    AuthLDAP ldap:// connect
      ldap_uri:        ldap://ldap.your-domain.local:389
      Ldap_connect:    Success
      isConnected:     true
      bind user:       ldaptest
      bind domain:     your-domain
      bind ldap_user:  your-domain\ldaptest
      isAuthenticated: true
      Ldap_connect:    Success

    AuthLDAP test LDAPs config
      File C:\Openldap\sysconf\ldap.conf found
      TLS_REQCERT:
        04: TLS_REQCERT never OK
      TLS_CACERT:
        07: TLS_CACERT C:\MAMP\ssl\cacert.pem
          File C:\MAMP\ssl\cacert.pem found

    AuthLDAP ldaps:// connect
      ldap_uri:        ldaps://ldap.your-domain.local:636
      Ldap_connect:    Success
      isConnected:     true
      bind user:
      bind domain:     your-domain
      bind ldap_user:  your-domain\ldaptest
      isAuthenticated: true
      Ldap_connect:    Success

    ```

### Manual Setup

1. AuthLDAP.php

    Copy the **AuthLDAP.php** from **vendor/rakoitde/shieldldap/src/Config/** into your project's config folder and update the namespace to `Config`. You will also need to have these classes extend the original classes. See the example below.

    ```php
    // new file - app/Config/AuthLDAP.php
    <?php

    declare(strict_types=1);

    //namespace Rakoitde\Shieldldap\Config;
    namespace Config;

    use App\Config\Auth;
    use Rakoitde\Shieldldap\Config\AuthLDAP as ShieldAuthLDAP;

    /**
     * LDAP Authenticator Configuration
     */
    class AuthLDAP extends ShieldAuthLDAP
    {
    ```

    Configure **app/Config/AuthLDAP.php** for your needs.

    It is recommended that the AuthLdap settings are made in the .env to prevent internal company information from becoming public

    ```console
    #--------------------------------------------------------------------
    # Shield AuthLdap
    #--------------------------------------------------------------------

    authldap.ldap_host              = 
    authldap.ldap_domain            = 
    authldap.search_base            = 
    authldap.storePasswordInSession = false
    authldap.use_ldaps              = true
    authldap.username               = 
    authldap.password               = 
    ```

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
        ]
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
        'session' => ['except' => ['login*', 'register', 'auth/a/*']],
    ],
```

You can also specify the filter should run on one or more routes within the routes
file itself:

```php
//service('auth')->routes($routes);
service('auth')->routes($routes, ['except' => ['login']]);
$routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView');
$routes->post('login', '\Rakoitde\Shieldldap\Controllers\LoginController::ldapLogin');
```


