<?php

// app/Controllers/Auth/LoginController.php
declare(strict_types=1);

namespace Rakoitde\Shieldldap\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Passwords;
// use CodeIgniter\Shield\Config\AuthSession;

use Rakoitde\Shieldldap\Authentication\Authenticators\LDAP;

use Rakoitde\Shieldldap\Config\AuthLDAP;

class LoginController extends BaseController
{
    /**
     * Authenticate Existing User and Issue JWT.
     */
    public function ldapLogin(): RedirectResponse
    {
        // Get the validation rules
        $rules = $this->getValidationRules();
        // Validate credentials
        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Get the credentials for login
        $credentials['username'] = $this->request->getPost('username');
        $credentials['password'] = $this->request->getPost('password');
        $remember                = (bool) $this->request->getPost('remember');

        /** @var LDAP $authenticator */
        $authenticator = auth('ldap')->getAuthenticator();

        // Attempt to login
        $result = $authenticator->remember($remember)->attempt($credentials);

        // Credentials mismatch.
        if (! $result->isOK()) {
            return redirect()->route('login')->withInput()->with('error', $result->reason());
        }

        // If an action has been defined for login, start it up.
        if ($authenticator->hasAction()) {
            return redirect()->route('auth-action-show')->withCookies();
        }

        return redirect()->to(config('Auth')->loginRedirect())->withCookies();
    }

    /**
     * Returns the rules that should be used for validation.
     *
     * @return array<string, array<string, array<string>|string>>
     * @phpstan-return array<string, array<string, string|list<string>>>
     */
    protected function getValidationRules(): array
    {
        return setting('Validation.login') ?? [
            'username' => [
                'label' => 'Auth.username',
                'rules' => config(AuthLDAP::class)->usernameValidationRules,
            ],
            'password' => [
                'label'  => 'Auth.password',
                'rules'  => 'required|' . Passwords::getMaxLengthRule(),
                'errors' => [
                    'max_byte' => 'Auth.errorPasswordTooLongBytes',
                ],
            ],
        ];
    }
}
