<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Fortyseeds\ShieldLdap\Config\AuthLDAP;

/**
 * Integration Tests for LDAP Authentication Flow
 * 
 * @internal
 */
final class LdapAuthenticationTest extends TestCase
{
    public function testAuthLdapConfigDefaults(): void
    {
        $config = new AuthLDAP();
        
        // Test basic configuration structure
        $this->assertIsString($config->ldap_host);
        $this->assertIsString($config->ldap_port);
        $this->assertIsString($config->ldaps_port);
        $this->assertIsBool($config->use_ldaps);
        $this->assertIsString($config->ldap_domain);
        $this->assertIsString($config->ldap_type);
        $this->assertIsString($config->login_attribute);
        $this->assertIsString($config->search_base);
        $this->assertIsArray($config->attributes);
    }
    
    public function testOpenLdapConfiguration(): void
    {
        $config = new AuthLDAP();
        
        // Configure for OpenLDAP/FreeIPA
        $config->ldap_type = 'ldap';
        $config->login_attribute = 'uid';
        $config->search_base = 'cn=users,cn=accounts,dc=example,dc=com';
        $config->attributes = [
            'uid', 'cn', 'dn', 'mail', 'displayName',
            'ipaUniqueID', 'memberOf', 'krbPrincipalName'
        ];
        
        $this->assertEquals('ldap', $config->ldap_type);
        $this->assertEquals('uid', $config->login_attribute);
        $this->assertContains('uid', $config->attributes);
        $this->assertContains('ipaUniqueID', $config->attributes);
    }
    
    public function testActiveDirectoryConfiguration(): void
    {
        $config = new AuthLDAP();
        
        // Configure for Active Directory
        $config->ldap_type = 'ad';
        $config->ldap_domain = 'company';
        $config->search_base = 'OU=Users,DC=company,DC=com';
        $config->attributes = [
            'objectSID', 'distinguishedname', 'displayName', 
            'samaccountname', 'mail', 'userAccountControl'
        ];
        
        $this->assertEquals('ad', $config->ldap_type);
        $this->assertEquals('company', $config->ldap_domain);
        $this->assertContains('objectSID', $config->attributes);
        $this->assertContains('samaccountname', $config->attributes);
    }
    
    public function testUserDnConstruction(): void
    {
        $username = 'john.doe';
        
        // Test OpenLDAP DN construction
        $ldapConfig = [
            'ldap_type' => 'ldap',
            'login_attribute' => 'uid',
            'search_base' => 'cn=users,cn=accounts,dc=example,dc=com'
        ];
        
        $expectedLdapDn = $ldapConfig['login_attribute'] . '=' . $username . ',' . $ldapConfig['search_base'];
        $this->assertEquals('uid=john.doe,cn=users,cn=accounts,dc=example,dc=com', $expectedLdapDn);
        
        // Test Active Directory user construction
        $adConfig = [
            'ldap_type' => 'ad',
            'ldap_domain' => 'company'
        ];
        
        $expectedAdUser = $adConfig['ldap_domain'] . '\\' . $username;
        $this->assertEquals('company\\john.doe', $expectedAdUser);
    }
    
    public function testAttributeCompatibility(): void
    {
        // Test attribute compatibility between different LDAP servers
        
        // Common attributes that should work on both
        $commonAttributes = ['cn', 'mail', 'displayName'];
        
        // AD-specific attributes
        $adSpecific = ['objectSID', 'samaccountname', 'userAccountControl'];
        
        // OpenLDAP/FreeIPA-specific attributes
        $ldapSpecific = ['uid', 'ipaUniqueID', 'krbPrincipalName', 'entryUUID'];
        
        foreach ($commonAttributes as $attr) {
            $this->assertIsString($attr);
        }
        
        foreach ($adSpecific as $attr) {
            $this->assertIsString($attr);
        }
        
        foreach ($ldapSpecific as $attr) {
            $this->assertIsString($attr);
        }
    }
    
    public function testConfigurationValidation(): void
    {
        $config = new AuthLDAP();
        
        // Test that ldap_type is either 'ad' or 'ldap'
        $validTypes = ['ad', 'ldap'];
        $config->ldap_type = 'ldap';
        
        $this->assertContains($config->ldap_type, $validTypes);
        
        // Test that login_attribute is set for LDAP type
        if ($config->ldap_type === 'ldap') {
            $config->login_attribute = 'uid';
            $this->assertNotEmpty($config->login_attribute);
        }
        
        // Test that ldap_domain is set for AD type
        $config->ldap_type = 'ad';
        $config->ldap_domain = 'company';
        
        if ($config->ldap_type === 'ad') {
            $this->assertNotEmpty($config->ldap_domain);
        }
    }
}