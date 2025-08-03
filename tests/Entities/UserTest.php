<?php

declare(strict_types=1);

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use Fortyseeds\ShieldLdap\Entities\User;

/**
 * Tests for Enhanced User Entity
 * 
 * @internal
 */
final class UserTest extends TestCase
{
    private User $user;
    
    protected function setUp(): void
    {
        $this->user = new User();
    }
    
    public function testLdapAttributesEmpty(): void
    {
        $this->user->ldap_attributes = null;
        
        $attributes = $this->user->ldapAttributes();
        
        $this->assertInstanceOf(\stdClass::class, $attributes);
    }
    
    public function testLdapAttributesWithValidJson(): void
    {
        $testData = ['mail' => 'test@example.com', 'cn' => 'Test User'];
        $this->user->ldap_attributes = json_encode($testData);
        
        $attributes = $this->user->ldapAttributes();
        
        $this->assertInstanceOf(\stdClass::class, $attributes);
        $this->assertEquals('test@example.com', $attributes->mail);
        $this->assertEquals('Test User', $attributes->cn);
    }
    
    public function testLdapAttributeSpecific(): void
    {
        $testData = ['mail' => 'test@example.com', 'displayName' => 'Test User'];
        $this->user->ldap_attributes = json_encode($testData);
        
        $mail = $this->user->ldapAttribute('mail');
        $displayName = $this->user->ldapAttribute('displayName');
        $missing = $this->user->ldapAttribute('nonexistent');
        
        $this->assertEquals('test@example.com', $mail);
        $this->assertEquals('Test User', $displayName);
        $this->assertEquals('', $missing);
    }
    
    public function testLdapAttributeWithNullData(): void
    {
        $this->user->ldap_attributes = null;
        
        $result = $this->user->ldapAttribute('mail');
        
        $this->assertEquals('', $result);
    }
    
    public function testTouchIdentityWithNull(): void
    {
        // This should not throw an exception
        $this->user->touchIdentity(null);
        
        // If we get here, the method handled null gracefully
        $this->assertTrue(true);
    }
    
    public function testUserAccountControlMethods(): void
    {
        // Test when no userAccountControl attribute exists
        $this->user->ldap_attributes = json_encode(['mail' => 'test@example.com']);
        
        $this->assertNull($this->user->isLdapAccountDisabled());
        $this->assertNull($this->user->isLdapAccountEnabled());
    }
}