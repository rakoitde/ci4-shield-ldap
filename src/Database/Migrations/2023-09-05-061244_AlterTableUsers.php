<?php

namespace Rakoitde\Shieldldap\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTableUsers extends Migration
{
    public function up()
    {
        $fields = [
            'mail'            => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'username'],
            'object_sid'      => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'mail'],
            'dn'              => ['type' => 'VARCHAR', 'constraint' => 50, 'after' => 'object_sid'],
            'ldap_attributes' => ['type' => 'TEXT', 'after' => 'dn'],
            'ldap_group_sids' => ['type' => 'TEXT', 'after' => 'ldap_attributes'],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['mail', 'object_sid', 'dn', 'ldap_attributes', 'ldap_group_sids']);
    }
}
