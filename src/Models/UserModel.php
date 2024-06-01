<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,

            'mail', 'object_sid', 'dn', 'ldap_attributes', 'ldap_group_sids',
        ];
    }
}
