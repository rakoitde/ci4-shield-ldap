<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Entities;

use CodeIgniter\Shield\Entities\User as ShieldUserEntity;
use Rakoitde\Shieldldap\Authentication\LDAPManager;
use stdClass;

class User extends ShieldUserEntity
{
    public function ldapAttributes(): stdClass
    {
        if (null === $this->ldap_attributes) {
            return new stdClass();
        }

        try {
            $ldapAttributes = json_decode($this->ldap_attributes);
        } catch (Exception $e) {
            return new stdClass();
        }

        return $ldapAttributes;
    }

    public function ldapAttribute(string $attribute): string
    {
        if (null === $this->ldap_attributes) {
            return '';
        }

        try {
            $ldapAttributes = json_decode($this->ldap_attributes);
        } catch (Exception $e) {
            return '';
        }

        return $ldapAttributes->{$attribute} ?? '';
    }

    /**
     * Check if user account is disabled
     */
    public function isLdapAccountDisabled(): ?bool
    {
        if ($this->ldapAttribute('userAccountControl') === '') {
            return null;
        }

        return ((int) ($this->ldapAttribute('UserAccountControl')) & LDAPManager::UAC_ACCOUNTDISABLE) === LDAPManager::UAC_ACCOUNTDISABLE;
    }

    /**
     * Check if user account is disabled
     */
    public function isLdapAccountEnabled(): ?bool
    {
        if ($this->ldapAttribute('userAccountControl') === '') {
            return null;
        }

        return ! (((int) ($this->ldapAttribute('UserAccountControl')) & LDAPManager::UAC_ACCOUNTDISABLE) === LDAPManager::UAC_ACCOUNTDISABLE);
    }
}
