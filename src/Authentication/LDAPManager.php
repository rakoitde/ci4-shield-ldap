<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Authentication;

use CodeIgniter\Shield\Entities\User;
use LDAP\Connection;

/**
 * LDAP Manager
 */
class LDAPManager
{
    protected string $username;
    protected string $password;
    protected Connection|bool $connection;
    protected bool $bind = false;

    public function __construct(string $username, string $password)
    {

        $this->username = $username;
        $this->password = $password;

        $this->connect();

    }

    /**
     * Connect to LDAP host
     */
    private function connect()
    {

        $ldap_host = config('AuthLDAP')->ldap_host;

        $this->connection = @ldap_connect($ldap_host);

        if ($this->isConnected()) {
            $this->auth();
        }

    }

    /**
     * Check if it ist connected
     */
    public function isConnected(): bool
    {
        return $this->connection !== false;
    }

    /**
     *  Authenticate user against LDAP host
     */
    public function auth()
    {

        $ldap_domain = config('AuthLDAP')->ldap_domain;
        $ldap_user   = $ldap_domain . '\\' . $this->username;

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $this->bind = @ldap_bind($this->connection, $ldap_user, $this->password);
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->bind !== false;
    }

    /**
     * Get user attributes
     */
    public function getAttributes(): array
    {

        $samaccountname = $this->username;
        $base           = 'dc=int,dc=kkh-services,dc=local';
        $filter         = "(samaccountname={$samaccountname})";
        $attributes     = ['objectSID', 'distinguishedname', 'displayName', 'description', 'cn', 'givenName', 'sn', 'mail', 'co', 'mobile', 'company', 'displayName', 'samaccountname', 'thumbnailPhoto'];

        $result = @ldap_search($this->connection, $base, $filter, $attributes);
        d($result);
        echo 'ldap_error: ' . ldap_error($this->connection);
        ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
        echo "ldap_get_option: {$err}";

        $aduser = ldap_first_entry($this->connection, $result);

        $adattributes = ldap_get_attributes($this->connection, $aduser);

        $dn = ldap_get_dn($this->connection, $aduser);

        d($attributes, $aduser, $adattributes, $dn);

        // <img src="data:image/jpeg;base64,<?php echo base64_encode($aduser['thumbnailPhoto'][0]); ? >" />
        return $attributes;
    }
}
