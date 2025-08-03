<?php

declare(strict_types=1);

namespace Fortyseeds\ShieldLdap\Config;

use App\Config\Auth;
use CodeIgniter\Config\BaseConfig;

// use Fortyseeds\ShieldLdap\Config\AuthLDAP as ShieldAuthLDAP;

/**
 * LDAP Authenticator Configuration
 */
class AuthLDAP extends BaseConfig // class AuthLDAP extends ShieldAuthLDAP
{
    public const RECORD_LOGIN_ATTEMPT_NONE    = 0; // Do not record at all
    public const RECORD_LOGIN_ATTEMPT_FAILURE = 1; // Record only failures
    public const RECORD_LOGIN_ATTEMPT_ALL     = 2; // Record all login attempts

    /**
     * The ldap hostname to connect to
     */
    public string $ldap_host = 'ldap://ldap.example.com/';

    /**
     * The ldap port to connect to
     */
    public string $ldap_port = '389';

    /**
     * The ldaps port to connect to
     */
    public string $ldaps_port = '636';

    /**
     * Use ldaps
     */
    public bool $use_ldaps = true;

    /**
     * The ldaps domain to extend the user like "example\username"
     * Only used when ldap_type = 'ad'
     * 
     * Examples:
     * - For domain.com: set to 'domain'
     * - User 'john' becomes 'domain\john'
     */
    public string $ldap_domain = 'example';

    /**
     * LDAP type: 'ad' for Active Directory, 'ldap' for standard LDAP/OpenLDAP/FreeIPA
     * 
     * AD (Active Directory):
     * - Uses domain\username format for authentication
     * - Set ldap_type = 'ad'
     * - Set ldap_domain = 'yourdomain.com'
     * 
     * OpenLDAP/FreeIPA/389 Directory:
     * - Uses DN format like uid=username,cn=users,cn=accounts,dc=domain,dc=com
     * - Set ldap_type = 'ldap'
     * - Set login_attribute = 'uid' (or 'cn' depending on your schema)
     */
    public string $ldap_type = 'ad';

    /**
     * Login attribute for building user DN when ldap_type = 'ldap'
     * 
     * Common values:
     * - 'uid' for OpenLDAP/FreeIPA/389 Directory
     * - 'cn' for some LDAP implementations
     * - 'samaccountname' for AD (but use ldap_type = 'ad' instead)
     * 
     * This creates DNs like: uid=username,cn=users,cn=accounts,dc=domain,dc=com
     */
    public string $login_attribute = 'uid';

    /**
     * Service account username for LDAP binding
     * 
     * AD: Can be 'domain\serviceaccount' or 'serviceaccount@domain.com'
     * OpenLDAP: Full DN like 'cn=admin,dc=domain,dc=com'
     */
    public string $username = 'username';

    /**
     * Service account password for LDAP binding
     */
    public string $password = 'password';

    /**
     * The LDAP search base for finding users
     * 
     * Examples:
     * - AD: 'OU=Users,DC=company,DC=local'
     * - OpenLDAP: 'ou=people,dc=company,dc=local'
     * - FreeIPA: 'cn=users,cn=accounts,dc=domain,dc=com'
     */
    public string $search_base = '';

    /**
     * The LDAP attributes to retrieve
     * 
     * Active Directory attributes:
     * ['objectSID', 'distinguishedname', 'displayName', 'title', 'description', 'cn', 'givenName', 'sn',
     *  'mail', 'co', 'telephoneNumber', 'mobile', 'company', 'department', 'l', 'postalCode', 'streetAddress',
     *  'samaccountname', 'thumbnailPhoto', 'userAccountControl']
     * 
     * OpenLDAP/FreeIPA attributes (default):
     * ['uid', 'cn', 'dn', 'distinguishedName', 'entryUUID', 'entryDN', 'displayName', 'title', 'description',
     *  'givenName', 'sn', 'mail', 'telephoneNumber', 'mobile', 'o', 'ou', 'l', 'postalCode', 'street',
     *  'employeeNumber', 'employeeType', 'departmentNumber', 'krbPrincipalName', 'krbCanonicalName',
     *  'ipaUniqueID', 'memberOf']
     *
     * @var list<string>
     */
    public array $attributes = [
        'uid', 'cn', 'dn', 'distinguishedName', 'entryUUID', 'entryDN',
        'displayName', 'title', 'description', 'givenName', 'sn', 'mail',
        'telephoneNumber', 'mobile', 'o', 'ou', 'l', 'postalCode', 'street',
        'employeeNumber', 'employeeType', 'departmentNumber',
        'krbPrincipalName', 'krbCanonicalName',
        'ipaUniqueID', 'memberOf'
    ];

    /**
     * Store encrypted Password in session
     */
    public bool $storePasswordInSession = false;

    /**
     * /**
     * --------------------------------------------------------------------
     * Record Login Attempts
     * --------------------------------------------------------------------
     * Whether login attempts are recorded in the database.
     *
     * Valid values are:
     * - Auth::RECORD_LOGIN_ATTEMPT_NONE
     * - Auth::RECORD_LOGIN_ATTEMPT_FAILURE
     * - Auth::RECORD_LOGIN_ATTEMPT_ALL
     */
    public int $recordLoginAttempt = self::RECORD_LOGIN_ATTEMPT_ALL;

    /**
     * The validation rules for username
     *
     * @var list<string>
     */
    public array $usernameValidationRules = [
        'required',
        'max_length[30]',
        'min_length[3]',
        'regex_match[/\A[a-zA-Z0-9\.]+\z/]',
    ];
}
