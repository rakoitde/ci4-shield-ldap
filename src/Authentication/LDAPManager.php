<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Authentication;

use CodeIgniter\Shield\Entities\User;
use LDAP\Connection;
use Rakoitde\Shieldldap\Config\AuthLDAP;
use UnexpectedValueException;

/**
 * LDAP Manager
 */
class LDAPManager
{
    public const UAC_SCRIPT                         = 1;   // hex = 0x0001
    public const UAC_ACCOUNTDISABLE                 = 2;   // hex = 0x0002
    public const UAC_HOMEDIR_REQUIRED               = 8;   // hex = 0x0008
    public const UAC_LOCKOUT                        = 16;   // hex = 0x0010
    public const UAC_PASSWD_NOTREQD                 = 32;   // hex = 0x0020
    public const UAC_PASSWD_CANT_CHANGE             = 64;   // hex = 0x0040
    public const UAC_ENCRYPTED_TEXT_PWD_ALLOWED     = 128;   // hex = 0x0080
    public const UAC_TEMP_DUPLICATE_ACCOUNT         = 256;   // hex = 0x0100
    public const UAC_NORMAL_ACCOUNT                 = 512;   // hex = 0x0200
    public const UAC_INTERDOMAIN_TRUST_ACCOUNT      = 2048;   // hex = 0x0800
    public const UAC_WORKSTATION_TRUST_ACCOUNT      = 4096;   // hex = 0x1000
    public const UAC_SERVER_TRUST_ACCOUNT           = 8192;   // hex = 0x2000
    public const UAC_DONT_EXPIRE_PASSWORD           = 65536;   // hex = 0x10000
    public const UAC_MNS_LOGON_ACCOUNT              = 131072;   // hex = 0x20000
    public const UAC_SMARTCARD_REQUIRED             = 262144;   // hex = 0x40000
    public const UAC_TRUSTED_FOR_DELEGATION         = 524288;   // hex = 0x80000
    public const UAC_NOT_DELEGATED                  = 1048576;   // hex = 0x100000
    public const UAC_USE_DES_KEY_ONLY               = 2097152;   // hex = 0x200000
    public const UAC_DONT_REQ_PREAUTH               = 4194304;   // hex = 0x400000
    public const UAC_PASSWORD_EXPIRED               = 8388608;   // hex = 0x800000
    public const UAC_TRUSTED_TO_AUTH_FOR_DELEGATION = 16777216;   // hex = 0x1000000
    public const UAC_PARTIAL_SECRETS_ACCOUNT        = 67108864;   // hex = 0x04000000
    public const LDAP_FORMAT_USERNAME_DEFAULT       = 'DLN';   // Down-Level Logon Name (domain\username)

    protected string $username;
    protected string $password;
    protected bool|Connection $connection;
    protected bool $bind = false;
    protected string $dn;
    protected string $ldap_error;
    protected string $ldap_diagnostic_message;
    protected array $attributes;
    protected array $group_sids;
    protected array $userAccountControl;
    protected AuthLDAP $config;

    public function __construct(string $username, string $password)
    {
        $this->config = config('AuthLDAP');

        $this->username = $username;
        $this->password = $password;

        $this->connect();
    }

    /**
     * Connect to LDAP host
     */
    private function connect()
    {
        if ($this->config->use_ldaps) {
            // ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
            $ldapuri = 'ldaps://' . $this->config->ldap_host . ':' . $this->config->ldaps_port;
        } else {
            $ldapuri = 'ldap://' . $this->config->ldap_host . ':' . $this->config->ldap_port;
        }

        $messages = ['ldapuri' => $ldapuri];

        log_message('info', 'LDAP connect to LdapUri {ldapuri}', $messages);

        $this->connection = @ldap_connect($ldapuri);

        if ($this->connection) {
            log_message('info', 'LDAP connect: syntactic check of the provided parameter successful');
        } else {
            log_message('error', 'LDAP connect: syntactic check failed. please check ldap parameter');
        }

        if ($this->isConnected()) {
            $this->auth();
        }

        if ($this->isAuthenticated()) {
            $this->attributes = $this->loadAttributes();
            $this->group_sids = $this->loadTokengroups();
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
     * Check if it ist connected
     */
    public function getConnection(): bool
    {
        return $this->connection !== false;
    }

    /**
     *  Authenticate user against LDAP host
     */
    public function auth()
    {
        $ldap_domain      = config('AuthLDAP')->ldap_domain;
        $ldap_user_format = config('AuthLDAP')->ldap_user_format ?? self::LDAP_FORMAT_USERNAME_DEFAULT;

        switch (strtoupper($ldap_user_format)) {
            case 'UPN':
                $ldap_user = $this->username . '@' . $ldap_domain;
                break;
            case 'DLN':
            default:
                $ldap_user = $ldap_domain . '\\' . $this->username;
                break;
        }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $this->bind = @ldap_bind($this->connection, $ldap_user, $this->password);

        if (ldap_error($this->connection) !== "Success") {
            $errno = strval(ldap_errno($this->connection));
            $error = json_encode(ldap_error($this->connection));
            log_message('error', 'LDAP auth Error #{errno}: {error}', ['errno' => $errno, 'error' => $error]);
        } else {
            log_message('info', 'LDAP auth successful');
        }
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
    public function loadAttributes(?string $username = null, ?array $ldapAttributes = null): ?array
    {
        $samaccountname = $username ?? $this->username;
        $ldapAttributes ??= $this->config->attributes;
        $filter = "(samaccountname={$samaccountname})";
        $result = @ldap_search($this->connection, $this->config->search_base, $filter, $ldapAttributes);

        if ($result === false) {
            $this->ldap_error = ldap_error($this->connection);

            ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
            $this->ldap_diagnostic_message = $err;

            return [];
        }

        $aduser = ldap_first_entry($this->connection, $result);

        if ($aduser === false) {
            return null;
        }

        $adattributes = ldap_get_attributes($this->connection, $aduser);

        $dn       = ldap_get_dn($this->connection, $aduser);
        $this->dn = $dn;

        $adCount    = (int) $adattributes['count'];
        $attributes = [];

        for ($i = 0; $i < $adCount; $i++) {
            $key = $adattributes[$i];

            switch ($key) {
                case 'objectSid':
                    $value = $this->sid_decode($adattributes[$key][0]);
                    break;

                case 'thumbnailPhoto':
                    $value = base64_encode($adattributes[$key][0]);
                    break;

                default:
                    $value = $adattributes[$key][0];
                    break;
            }
            $attributes[$key] = $value;
        }

        $this->attributes = $attributes;

        return $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getThumbnailImage()
    {
        if (! isset($this->adattributes['thumbnailPhoto'])) {
            return '';
        }

        return '<img src="data:image/jpeg;base64,' . $this->adattributes['thumbnailPhoto'] . '">';
    }

    public function loadTokengroups(bool $return_group_sids = true)
    {
        if (! isset($this->dn)) {
            return [];
        }

        $result = ldap_read($this->connection, $this->dn, 'CN=*', ['tokengroups']);

        $tokegroups = ldap_get_entries($this->connection, $result);

        $groups = [];

        if ((int) $tokegroups['count'] > 0) {
            $groups = $tokegroups[0]['tokengroups'];
            unset($groups['count']);

            foreach ($groups as $i => &$sid) {
                $sid = $this->sid_decode($sid);

                if ($return_group_sids) {
                    $groups[$i] = $sid;

                    continue;
                }

                $sid_dn = ldap_read($this->connection, "<SID={$sid}>", 'CN=*', ['dn']);
                if ($sid_dn !== false) {
                    $group      = ldap_get_entries($this->connection, $sid_dn);
                    $group      = $group['count'] === 1 ? $group[0]['dn'] : null;
                    $groups[$i] = $group;
                }
            }
        }

        return $groups;
    }

    public function loadUserAccountControl()
    {
        if (! isset($this->adattributes['UserAccountControl'])) {
            return '';
        }
    }

    /**
     * Check if user account is disabled
     */
    public function isAccountDisabled(): bool
    {
        if (! isset($this->adattributes['UserAccountControl'])) {
            return '';
        }

        return ($this->adattributes['UserAccountControl'] & self::UAC_ACCOUNTDISABLE) === self::UAC_ACCOUNTDISABLE;
    }

    public function getGroupSids()
    {
        return $this->group_sids;
    }

    /**
     * Decode the binary SID into its readable form.
     *
     * @param string $value
     *
     * @return string
     */
    private function sid_decode($value)
    {
        $sid            = @unpack('C1rev/C1count/x2/N1id', $value);
        $subAuthorities = [];

        if (! isset($sid['id']) || ! isset($sid['rev'])) {
            throw new UnexpectedValueException(
                'The revision level or identifier authority was not found when decoding the SID.'
            );
        }

        $revisionLevel       = $sid['rev'];
        $identifierAuthority = $sid['id'];
        $subs                = $sid['count'] ?? 0;

        // The sub-authorities depend on the count, so only get as many as the count, regardless of data beyond it
        for ($i = 0; $i < $subs; $i++) {
            $subAuthorities[] = unpack('V1sub', hex2bin(substr(bin2hex($value), 16 + ($i * 8), 8)))['sub'];
        }

        return 'S-' . $revisionLevel . '-' . $identifierAuthority . implode(
            '',
            preg_filter('/^/', '-', $subAuthorities)
        );
    }

    public function ldapAttribute(string $attribute): string
    {
        return $this->attributes[$attribute] ?? '';
    }

    /**
     * Check if user account is disabled
     */
    public function isLdapAccountDisabled(): ?bool
    {
        if ($this->ldapAttribute('userAccountControl') === '') {
            return null;
        }

        return ((int) ($this->ldapAttribute('userAccountControl')) & LDAPManager::UAC_ACCOUNTDISABLE) === LDAPManager::UAC_ACCOUNTDISABLE;
    }

    /**
     * Check if user account is disabled
     */
    public function isLdapAccountEnabled(): ?bool
    {
        if ($this->ldapAttribute('userAccountControl') === '') {
            return null;
        }

        return ((int) ($this->ldapAttribute('userAccountControl')) & LDAPManager::UAC_ACCOUNTDISABLE) !== LDAPManager::UAC_ACCOUNTDISABLE;
    }
}
