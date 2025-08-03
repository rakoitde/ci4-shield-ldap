# Enhanced LDAP Authentication for CodeIgniter 4 Shield

Enhanced version of the LDAP Authentication library for CodeIgniter 4 Shield that supports both **Active Directory** and **OpenLDAP/FreeIPA/389 Directory** servers.

## Features

- ✅ **Active Directory** support (domain\username format)
- ✅ **OpenLDAP/FreeIPA/389 Directory** support (DN format)
- ✅ Automatic user creation on first login
- ✅ LDAP attribute synchronization
- ✅ Group membership handling
- ✅ Configurable attribute mapping
- ✅ Shield-compatible user identity management

## Installation

```bash
composer require fortyseeds/ci4-shield-ldap
```

## Configuration

### 1. Basic Setup

Copy the configuration file to your app:

```bash
cp vendor/fortyseeds/ci4-shield-ldap/src/Config/AuthLDAP.php app/Config/AuthLDAP.php
```

### 2. Configure for Active Directory

```php
// app/Config/AuthLDAP.php
public string $ldap_host = 'ldap://dc.company.com';
public string $ldap_port = '389';
public bool $use_ldaps = true; // Use port 636 for LDAPS
public string $ldap_type = 'ad';
public string $ldap_domain = 'company'; // For domain\username format
public string $username = 'CN=Service Account,OU=Service Accounts,DC=company,DC=com';
public string $password = 'service_password';
public string $search_base = 'OU=Users,DC=company,DC=com';

// Active Directory attributes
public array $attributes = [
    'objectSID', 'distinguishedname', 'displayName', 'title', 'description', 
    'cn', 'givenName', 'sn', 'mail', 'co', 'telephoneNumber', 'mobile', 
    'company', 'department', 'l', 'postalCode', 'streetAddress',
    'samaccountname', 'thumbnailPhoto', 'userAccountControl'
];
```

### 3. Configure for OpenLDAP/FreeIPA

```php
// app/Config/AuthLDAP.php
public string $ldap_host = 'ldap://ipa.company.com';
public string $ldap_port = '389';
public bool $use_ldaps = true; // Use port 636 for LDAPS
public string $ldap_type = 'ldap';
public string $login_attribute = 'uid'; // or 'cn' depending on your schema
public string $username = 'cn=admin,dc=company,dc=com';
public string $password = 'admin_password';
public string $search_base = 'cn=users,cn=accounts,dc=company,dc=com';

// OpenLDAP/FreeIPA attributes
public array $attributes = [
    'uid', 'cn', 'dn', 'distinguishedName', 'entryUUID', 'entryDN',
    'displayName', 'title', 'description', 'givenName', 'sn', 'mail',
    'telephoneNumber', 'mobile', 'o', 'ou', 'l', 'postalCode', 'street',
    'employeeNumber', 'employeeType', 'departmentNumber',
    'krbPrincipalName', 'krbCanonicalName', 'ipaUniqueID', 'memberOf'
];
```

### 4. Update Shield Configuration

```php
// app/Config/Auth.php
public array $authenticators = [
    'ldap' => \Rakoitde\Shieldldap\Authentication\Authenticators\LDAP::class,
    'session' => \CodeIgniter\Shield\Authentication\Authenticators\Session::class,
    'tokens' => \CodeIgniter\Shield\Authentication\Authenticators\AccessTokens::class,
];

public array $authenticationChain = [
    'ldap',
    'session',
];
```

### 5. Database Migration

The package includes database migrations for additional LDAP fields. Run:

```bash
php spark migrate -all
```

## Usage

### Authentication Flow

1. **Active Directory**: Users login with `domain\username` or just `username`
2. **OpenLDAP/FreeIPA**: Users login with their `uid` (e.g., `john.doe`)

The system automatically:
- Authenticates against LDAP
- Creates local user account on first login
- Synchronizes LDAP attributes
- Manages user identity for Shield compatibility

### Testing LDAP Connection

```bash
php spark shieldldap:check
```

### Managing LDAP Users

```bash
php spark shieldldap:user
```

## Authentication Types Comparison

| Feature | Active Directory | OpenLDAP/FreeIPA |
|---------|------------------|-------------------|
| Login Format | `domain\username` | `uid` or `cn` |
| DN Format | Automatic via domain | `uid=user,cn=users,cn=accounts,dc=domain,dc=com` |
| Service Account | `domain\service` or `service@domain.com` | `cn=admin,dc=domain,dc=com` |
| Search Base | `OU=Users,DC=domain,DC=com` | `cn=users,cn=accounts,dc=domain,dc=com` |
| Primary Attributes | `samaccountname`, `objectSID` | `uid`, `ipaUniqueID` |

## Troubleshooting

### Common Issues

1. **"Cannot assign null to property"**: Ensure LDAP attributes exist in your directory
2. **"UserIdentity not found"**: The enhanced version automatically creates email identities
3. **Connection timeout**: Check firewall, ports (389/636), and network connectivity
4. **Authentication fails**: Verify service account credentials and permissions

### Debug Mode

Enable debug logging in your environment:

```bash
CI_ENVIRONMENT = development
```

Check logs in `writable/logs/` for detailed LDAP authentication information.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

MIT License - see LICENSE file for details.

## Credits

- Original package by [Ralf Kornberger](https://github.com/rakoitde)
- Enhanced by [FortySeeds](https://github.com/FortySeeds) for OpenLDAP/FreeIPA support
- Built for [CodeIgniter 4 Shield](https://github.com/codeigniter4/shield)

## Changelog

### v2.0.0 (Enhanced Version)
- ✅ Added OpenLDAP/FreeIPA/389 Directory support
- ✅ Flexible DN construction based on LDAP type
- ✅ Enhanced error handling and user identity management
- ✅ Improved attribute mapping for different LDAP servers
- ✅ Comprehensive documentation for both AD and OpenLDAP

### v1.x (Original)
- Active Directory support only