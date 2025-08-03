<?php

namespace Fortyseeds\ShieldLdap\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use CodeIgniter\Shield\Config\Auth;

class AlterTableUsers extends Migration
{

	/**
     * Auth Table names
     */
    private array $tables;

    private array $attributes;

    public function __construct(?Forge $forge = null)
    {
        /** @var Auth $authConfig */
        $authConfig = config('Auth');

        if ($authConfig->DBGroup !== null) {
            $this->DBGroup = $authConfig->DBGroup;
        }

        parent::__construct($forge);

        $this->tables     = $authConfig->tables;
        $this->attributes = ($this->db->getPlatform() === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }
    
    public function up(): void
    {
        $fields = [
            'mail'            => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'username'],
            'object_sid'      => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'mail'],
            'dn'              => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'object_sid'],
            'ldap_attributes' => ['type' => 'TEXT', 'after' => 'dn'],
            'ldap_group_sids' => ['type' => 'TEXT', 'after' => 'ldap_attributes'],
        ];

        $this->forge->addColumn($this->tables['users'], $fields);
    }

    public function down()
    {
        $this->forge->dropColumn($this->tables['users'], ['mail', 'object_sid', 'dn', 'ldap_attributes', 'ldap_group_sids']);
    }
}
