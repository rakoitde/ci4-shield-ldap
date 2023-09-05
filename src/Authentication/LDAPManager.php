<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Authentication;

use CodeIgniter\Shield\Entities\User;
use Rakoitde\Shieldldap\Config\AuthLDAP;
use LDAP\Connection;
use UnexpectedValueException;

/**
 * LDAP Manager
 */
class LDAPManager
{
    protected string $username;
    protected string $password;
    protected Connection|bool $connection;
    protected bool $bind = false;
    protected string $dn;
    protected string $ldap_error;
    protected string $ldap_diagnostic_message;
    protected array $attributes;
    protected array $group_sids;
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

        $this->connection = @ldap_connect($this->config->ldap_host);

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
    public function loadAttributes(): array
    {

        $samaccountname = $this->username;
        $filter         = "(samaccountname={$samaccountname})";

        $result = @ldap_search($this->connection, $this->config->search_base, $filter, $this->config->attributes);

        if ($result === false) {
            $this->ldap_error = ldap_error($this->connection);

            ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
            $this->ldap_diagnostic_message = $err;

            return [];
        }

        $aduser = ldap_first_entry($this->connection, $result);

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

        // <img src="data:image/jpeg;base64,<?php echo base64_encode($aduser['thumbnailPhoto'][0]); ? >" />
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
}
