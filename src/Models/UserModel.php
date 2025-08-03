<?php

declare(strict_types=1);

namespace Fortyseeds\ShieldLdap\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
use Fortyseeds\ShieldLdap\Entities\User;

class UserModel extends ShieldUserModel
{
    protected $returnType = User::class;

    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,

            'mail', 'object_sid', 'dn', 'ldap_attributes', 'ldap_group_sids',
        ];
    }
}
