<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use LDAP\Connection;
use Rakoitde\Shieldldap\Config\AuthLDAP;

class CheckCommand extends BaseCommand
{
    public const LDAP_FORMAT_USERNAME_DEFAULT = 'DLN';   // Down-Level Logon Name (domain\username)

    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Shield';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'shieldldap:check';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Check ShieldLdap configuration and connections';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'shieldldap:check [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--u' => 'username',
        '--p' => 'password',
    ];

    protected string $username = '';
    protected string $password = '';
    protected bool|Connection $connection;
    protected bool $bind = false;
    protected AuthLDAP $config;

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->username = $params['username'] ?? CLI::prompt('Username');
        $this->password = $params['password'] ?? CLI::prompt('password');

        $this->showConfig();
        $this->connectLdap(false);
        $this->testLdapsConfig();
        $this->connectLdap(true);
    }

    /**
     * Show ShieldLdap config
     */
    public function showConfig()
    {
        $ldapConfig = config('AuthLDAP');

        CLI::write('AuthLDAP config', 'yellow');

        CLI::write('  ldap_host:              ' . CLI::color($ldapConfig->ldap_host, 'white'), 'green');
        CLI::write('  ldap_port:              ' . CLI::color($ldapConfig->ldap_port, 'white'), 'green');
        CLI::write('  ldaps_port:             ' . CLI::color($ldapConfig->ldaps_port, 'white'), 'green');
        CLI::write('  use_ldaps:              ' . CLI::color($ldapConfig->use_ldaps ? 'true' : 'false', 'white'), 'green');
        CLI::write('  ldap_domain:            ' . CLI::color($ldapConfig->ldap_domain, 'white'), 'green');
        CLI::write('  ldap_format_username:   ' . CLI::color($ldapConfig->ldap_user_format . ' - ' . $this->getLdapFormatDescription($ldapConfig->ldap_user_format), 'white'), 'green');
        CLI::write('  search_base:            ' . CLI::color($ldapConfig->search_base, 'white'), 'green');
        CLI::write('  storePasswordInSession: ' . CLI::color($ldapConfig->storePasswordInSession ? 'true' : 'false', 'white'), 'green');
        CLI::write('  attributes:             ' . CLI::color(implode(', ', $ldapConfig->attributes), 'white'), 'green');

        CLI::newLine();
    }

    /**
     * Test LDAPs config file
     */
    private function testLdapsConfig()
    {
        CLI::write('AuthLDAP test LDAPs config', 'yellow');

        if (is_windows()) {
            $ldapConfFile = 'C:\\Openldap\\sysconf\\ldap.conf';
        } else {
            $ldapConfFile = '/etc/ldap/ldap.conf';
        }

        CLI::print('  File ', 'green');
        CLI::print($ldapConfFile, 'white');
        if (file_exists($ldapConfFile)) {
            CLI::print(' found', 'green');
        } else {
            CLI::print(' not found', 'red');
            CLI::newLine();

            return;
        }

        $lines = file($ldapConfFile);
        $count = 0;

        foreach ($lines as $line) {
            $count++;

            if (str_contains($line, 'TLS_REQCERT')) {
                CLI::write('  TLS_REQCERT: ', 'yellow');
                CLI::print('    ' . str_pad((string) $count, 2, '0', STR_PAD_LEFT) . ': ' . trim($line));
                if (str_contains($line, 'TLS_REQCERT never')) {
                    CLI::print(' OK', 'green');
                } else {
                    CLI::print(" => 'TLS_REQCERT never' expected", 'red');
                }
            }

            if (str_contains($line, 'TLS_CACERT')) {
                CLI::write('  TLS_CACERT: ', 'yellow');
                CLI::write('    ' . str_pad((string) $count, 2, '0', STR_PAD_LEFT) . ': ' . trim($line));

                $certFile = substr(trim($line), 11);
                CLI::print('      File ', 'green');
                CLI::print($certFile, 'white');
                if (file_exists($certFile)) {
                    CLI::print(' found', 'green');
                } else {
                    CLI::print(' => not found', 'red');
                }
            }
        }

        CLI::newLine();
    }

    /**
     * Connect to LDAP host
     */
    private function connectLdap(bool $ldaps = false)
    {
        $ldapConfig = config('AuthLDAP');

        $prefix = ($ldaps === true) ? 'ldaps://' : 'ldap://';
        CLI::write('AuthLDAP ' . $prefix . ' connect', 'yellow');

        $port    = ($ldaps === true) ? $ldapConfig->ldaps_port : $ldapConfig->ldap_port;
        $ldapuri = $prefix . $ldapConfig->ldap_host . ':' . $port;
        CLI::write('  ldap_uri:        ' . CLI::color($ldapuri, 'white'), 'green');

        $this->connection = @ldap_connect($ldapuri);

        $color = ldap_error($this->connection) === 'Success' ? 'white' : 'red';
        CLI::write('  Ldap_connect:    ' . CLI::color(ldap_error($this->connection), $color), 'green');

        $color = $this->connection !== false ? 'white' : 'red';
        CLI::write('  isConnected:     ' . CLI::color('true', $color), 'green');

        $bind = $this->bind();

        CLI::newLine();
    }

    /**
     *  Authenticate user against LDAP host
     */
    public function bind()
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

        CLI::write('  bind user:       ' . CLI::color($this->username, 'white'), 'green');
        CLI::write('  bind domain:     ' . CLI::color($ldap_domain, 'white'), 'green');
        CLI::write('  bind ldap_user:  ' . CLI::color($ldap_user, 'white'), 'green');

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $bind = @ldap_bind($this->connection, $ldap_user, $this->password);

        if ($bind !== false) {
            CLI::write('  isAuthenticated: ' . CLI::color('true', 'white'), 'green');
            CLI::write('  Ldap_connect:    ' . CLI::color(ldap_error($this->connection), 'white'), 'green');
        } else {
            CLI::write('  isAuthenticated: ' . CLI::color('false', 'red'), 'green');
            CLI::write('  Ldap_connect:    ' . CLI::color(ldap_error($this->connection), 'red'), 'green');
        }

        return $bind;
    }

    /**
     * Get LDAP format username description
     */
    private function getLdapFormatDescription(string $format): string
    {
        $descriptions = [
            'UPN' => 'User Principal Name (username@your-domain)',
            'DLN' => 'Down-Level Logon Name (your-domain\\username)',
        ];

        return $descriptions[$format] ?? 'Unknown format';
    }
}
