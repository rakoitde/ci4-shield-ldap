# CodeIgniter ShieldLDAP

ShieldLDAP is an CodeIgniter Shield Addon to authenticate against an LDAP Server.

See the [An Official Auth Library](https://github.com/codeigniter4/shield/blob/develop/README.md) for more Info.

## Authentication Methods

ShieldLDAP provides the **LDAP-based** method.

### LDAP

This is your typical username/password system you see everywhere. It includes a secure "remember me" functionality.
This can be used for standard web applications, as well as for single page applications. Includes full controllers and
basic views for all standard functionality, like registration, login, forgot password, etc.

## Getting Started

### Prerequisites

Usage of Shield requires the following:

- A [CodeIgniter 4.3.5+](https://github.com/codeigniter4/CodeIgniter4/) based project
- [Composer](https://getcomposer.org/) for package management
- PHP 7.4.3+

### Installation

Installation is done through Composer.

```console
composer require rakoitde/shieldldap dev-develop
```

### Config AuthLdap in .env

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

See the (docs)[docs] for more specific instructions on installation and usage recommendations.

## Contributing

ShieldLDAP does accept and encourage contributions from the community in any shape. It doesn't matter
whether you can code, write documentation, or help find bugs, all contributions are welcome.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE) file for details.