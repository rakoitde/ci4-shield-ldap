<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rakoitde\Shieldldap\Authentication\LDAPManager;
use Rakoitde\Shieldldap\Models\UserModel;
use Rakoitde\Shieldldap\Entities\User;

class UserCommand extends BaseCommand
{
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
    protected $name = 'shieldldap:user';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Manage ShieldLdap users.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'shieldldap:user <action> [options]

    shield:user create -n newusername';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'action' => '
    create:      Create a new user from Ldap query',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '-n'  => "User Name (samAccountName)",
        '-g'  => "Group name",
    ];

    protected array $possibleActions = [
        'create',
    ];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {

        if (!isset($params[0]) || !in_array($params[0], $this->possibleActions)) {
            $this->showHelp();
            return;
        }

        $action = $params[0];

        $this->username = $params['u'] ?? $params['username'] ?? CLI::prompt('Username');
        $this->group    = $params['g'] ?? $params['group'] ?? config("AuthGroups")->defaultGroup;

        $this->create();

    }

    public function create()
    {

        CLI::print('Create User: ' . CLI::color($this->username, 'white'), 'yellow');

        $authLdapConfig = config("AuthLDAP");
        $ldapManager = new LDAPManager($authLdapConfig->username, $authLdapConfig->password);

        if (!$ldapManager->isConnected()) {
            CLI::write("Not connected!");
            return;
        }

        $ldapAttributes = ['sAMAccountName', 'mail', 'objectSid', 'distinguishedName', 'userAccountControl'];
        $attributes = $ldapManager->loadAttributes($this->username, $ldapAttributes);

        $data = [
            'username'        => $attributes['sAMAccountName'],
            'mail'            => $attributes['mail'] ?? '',
            'object_sid'      => $attributes['objectSid'],
            'dn'              => $attributes['distinguishedName'],
            'ldap_attributes' => json_encode($ldapManager->loadAttributes($this->username)),
            'ldap_group_sids' => json_encode($ldapManager->loadTokengroups()),
            'active'          => $ldapManager->isLdapAccountEnabled(),
        ];

        $userModel = model(UserModel::class);
        $userEntity = $userModel->where('username', $this->username)->first();

        $prompt = 'Create?';
        if ($userEntity) {
            $data['id'] = $userEntity->id;
            $prompt = 'Update?';
            CLI::print(' ID: ' . CLI::color(strval($userEntity->id), 'white'), 'yellow');
        }

        CLI::newline();
        CLI::write('DistinguishedName: ' . CLI::color($data['dn'], 'white'), 'green');
        CLI::write('ObjectSid:         ' . CLI::color($data['object_sid'], 'white'), 'green');
        CLI::write('Mail:              ' . CLI::color($data['mail'], 'white'), 'green');
        CLI::write('Active:            ' . CLI::color($data['active'] ? 'true' : 'false', 'white'), 'green');
        CLI::newline();

        $overwrite = CLI::prompt($prompt, ['y', 'n']);

        if (trim($overwrite) == "y") {

            $user = new User($data);
            $userModel->save($user);

            $userId = $userModel->getInsertID();

            if ($userId) {
                $userEntity = $userModel->find($userId);
                $userEntity->addGroup($this->group);
                CLI::write('Group added: ' . CLI::color($this->group, 'white'), 'green');
                CLI::newline();
            }
        }

    }

}