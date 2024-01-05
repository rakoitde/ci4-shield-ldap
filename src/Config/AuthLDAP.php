<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Config;

use App\Config\Auth;
use CodeIgniter\Config\BaseConfig;
#use Rakoitde\Shieldldap\Config\AuthLDAP as ShieldAuthLDAP;

/**
 * LDAP Authenticator Configuration
 */
class AuthLDAP extends BaseConfig
#class AuthLDAP extends ShieldAuthLDAP
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
     * The ldaps domain to extend the user like "example\username"
     */
    public string $ldap_domain = 'example';

    /**
     * The ldaps searchbase like "dc=int,dc=company,dc=local" 
     */
    public string $search_base = '';

    /**
     * The ldap attributes
     *
     * @var string[]
     */
    public array $attributes = [
        'objectSID', 'distinguishedname', 'displayName', 'title', 'description', 'cn', 'givenName', 'sn', 'mail', 'co', 'telephoneNumber', 'mobile', 'company', 'department', 'l', 'postalCode', 'streetAddress','displayName', 'samaccountname', 'thumbnailPhoto'];

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
     * @var string[]
     */
    public array $usernameValidationRules = [
        'required',
        'max_length[30]',
        'min_length[3]',
        'regex_match[/\A[a-zA-Z0-9\.]+\z/]',
    ];
}
