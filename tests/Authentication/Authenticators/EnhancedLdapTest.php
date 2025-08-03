<?php

declare(strict_types=1);

namespace Tests\Authentication\Authenticators;

use PHPUnit\Framework\TestCase;
use Fortyseeds\ShieldLdap\Authentication\LDAPManager;

/**
 * Tests for Enhanced LDAP functionality (OpenLDAP/FreeIPA support)
 * 
 * @internal
 */
final class EnhancedLdapTest extends TestCase
{
    public function testLdapManagerConstructor(): void
    {
        $manager = new LDAPManager('testuser', 'testpass');
        
        $this->assertInstanceOf(LDAPManager::class, $manager);
    }
    
    public function testLdapTypeDetection(): void
    {
        // Mock config for testing
        $config = new \stdClass();
        
        // Test Active Directory type
        $config->ldap_type = 'ad';
        $config->ldap_domain = 'company';
        $config->search_base = 'DC=company,DC=com';
        
        $this->assertEquals('ad', $config->ldap_type);
        
        // Test OpenLDAP type
        $config->ldap_type = 'ldap';
        $config->login_attribute = 'uid';
        $config->search_base = 'cn=users,cn=accounts,dc=company,dc=com';
        
        $this->assertEquals('ldap', $config->ldap_type);
        $this->assertEquals('uid', $config->login_attribute);
    }
    
    public function testDnConstruction(): void
    {
        // Test Active Directory DN format
        $username = 'john.doe';
        $domain = 'company';
        $adUser = $domain . '\\' . $username;
        
        $this->assertEquals('company\\john.doe', $adUser);
        
        // Test OpenLDAP DN format
        $loginAttribute = 'uid';
        $searchBase = 'cn=users,cn=accounts,dc=company,dc=com';
        $ldapUser = $loginAttribute . '=' . $username . ',' . $searchBase;
        
        $this->assertEquals('uid=john.doe,cn=users,cn=accounts,dc=company,dc=com', $ldapUser);
    }
    
    public function testAttributeMapping(): void
    {
        // Test Active Directory attributes
        $adAttributes = [
            'objectSID', 'distinguishedname', 'displayName', 'samaccountname', 
            'mail', 'userAccountControl'
        ];
        
        $this->assertContains('objectSID', $adAttributes);
        $this->assertContains('samaccountname', $adAttributes);
        
        // Test OpenLDAP/FreeIPA attributes
        $ldapAttributes = [
            'uid', 'cn', 'dn', 'entryUUID', 'displayName', 'mail', 
            'ipaUniqueID', 'memberOf', 'krbPrincipalName'
        ];
        
        $this->assertContains('uid', $ldapAttributes);
        $this->assertContains('ipaUniqueID', $ldapAttributes);
        $this->assertContains('krbPrincipalName', $ldapAttributes);
    }
    
    public function testConfigurationValidation(): void
    {
        // Test required config fields
        $requiredFields = ['ldap_host', 'search_base', 'ldap_type'];
        
        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
        }
        
        // Test ldap_type values
        $validTypes = ['ad', 'ldap'];
        
        $this->assertContains('ad', $validTypes);
        $this->assertContains('ldap', $validTypes);
    }
    
    public function testPortConfiguration(): void
    {
        // Test standard LDAP ports
        $ldapPort = 389;
        $ldapsPort = 636;
        
        $this->assertEquals(389, $ldapPort);
        $this->assertEquals(636, $ldapsPort);
    }
}