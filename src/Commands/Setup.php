<?php

declare(strict_types=1);

namespace Rakoitde\Shieldldap\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Commands\Database\Migrate;
use CodeIgniter\Shield\Commands\Setup\ContentReplacer;
use Config\Services;

class Setup extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Shield';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'shieldldap:setup';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Initial setup for CodeIgniter Shield LDAP.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'shieldldap:setup';

    /**
     * the Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [
        '-f' => 'Force overwrite ALL existing files in destination.',
    ];

    /**
     * The path to `CodeIgniter\Shield\` src directory.
     *
     * @var string
     */
    protected $sourcePath;

    protected $distPath = APPPATH;
    private ContentReplacer $replacer;

    /**
     * Displays the help for the spark cli script itself.
     */
    public function run(array $params): void
    {
        $this->replacer = new ContentReplacer();

        $this->sourcePath = __DIR__ . '/../';

        $this->publishConfig();
    }

    private function publishConfig(): void
    {
        $this->publishConfigAuthLDAP();

        $this->updatesAuthConfig();

        $this->updatesRoutes();

        $this->runMigrations();
    }

    /**
     * @param string $file     Relative file path like 'Config/Auth.php'.
     * @param array  $replaces [search => replace]
     */
    protected function copyAndReplace(string $file, array $replaces): void
    {
        $path = "{$this->sourcePath}/{$file}";

        $content = file_get_contents($path);

        $content = $this->replacer->replace($content, $replaces);

        $this->writeFile($file, $content);
    }

    private function publishConfigAuthLDAP(): void
    {
        $file     = 'Config/AuthLDAP.php';
        $replaces = [
            'namespace Rakoitde\Shieldldap\Config' => 'namespace Config',
            'use CodeIgniter\\Config\\BaseConfig;' => 'use Rakoitde\\Shieldldap\\Config\\AuthLDAP as ShieldAuthLDAP;',
            'extends BaseConfig'                   => 'extends ShieldAuthLDAP',
        ];

        $this->copyAndReplace($file, $replaces);
    }

    /**
     * Write a file, catching any exceptions and showing a
     * nicely formatted error.
     *
     * @param string $file Relative file path like 'Config/Auth.php'.
     */
    protected function writeFile(string $file, string $content): void
    {
        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_exists($path)) {
            $overwrite = (bool) CLI::getOption('f');

            if (
                ! $overwrite
                && CLI::prompt("  File '{$cleanPath}' already exists in destination. Overwrite?", ['n', 'y']) === 'n'
            ) {
                CLI::error("  Skipped {$cleanPath}. If you wish to overwrite, please use the '-f' option or reply 'y' to the prompt.");

                return;
            }
        }

        if (write_file($path, $content)) {
            CLI::write(CLI::color('  Created: ', 'green') . $cleanPath);
        } else {
            CLI::error("  Error creating {$cleanPath}.");
        }
    }

    private function updatesAuthConfig(): void
    {
        $file = 'Config/Auth.php';

        $replaces = [
            'public string $defaultAuthenticator = \'session\';' => 'public string $defaultAuthenticator = \'ldap\';',
            'public bool $allowRegistration = true;' => 'public bool $allowRegistration = false;',
            'public bool $allowMagicLinkLogins = true;' => 'public bool $allowMagicLinkLogins = false;',
            'public array $validFields = [\'email\', // \'username\'];', 'public array $validFields = [\'username\'];',
            'public string $userProvider = UserModel::class;', 'public string $userProvider = \\Rakoitde\\Shieldldap\\Models\\UserModel::class;',
        ];

        if ($this->replace($file, $replaces)) {
            return;
        }
    }

    private function updatesRoutes(): void
    {
        $file = 'Config/Routes.php';

        $ldap_routes = <<<'EOT'
            service('auth')->routes($routes, ['except' => ['login', 'register']]);
            $routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView');
            $routes->post('login', '\Rakoitde\Shieldldap\Controllers\LoginController::ldapLogin');
            $routes->get('register', '\Rakoitde\Shieldldap\Controllers\RegisterController::registerView');
            $routes->post('register', '\Rakoitde\Shieldldap\Controllers\RegisterController::registerAction');
            EOT;

        $replaces = [
            'service(\'auth\')->routes($routes);' => $ldap_routes,
        ];

        if ($this->replace($file, $replaces)) {
            return;
        }
    }

    /**
     * Replace for setupHelper()
     *
     * @param string $file     Relative file path like 'Controllers/BaseController.php'.
     * @param array  $replaces [search => replace]
     */
    private function replace(string $file, array $replaces): bool
    {
        $path      = $this->distPath . $file;
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);

        $output = $this->replacer->replace($content, $replaces);

        if ($output === $content) {
            return false;
        }

        if (write_file($path, $output)) {
            CLI::write(CLI::color('  Updated: ', 'green') . $cleanPath);

            return true;
        }

        CLI::error("  Error updating {$cleanPath}.");

        return false;
    }

    private function runMigrations(): void
    {
        if (
            $this->cliPrompt('  Run `spark migrate --all` now?', ['y', 'n']) === 'n'
        ) {
            return;
        }

        $command = new Migrate(Services::logger(), Services::commands());
        $command->run(['all' => null]);
    }

    /**
     * This method is for testing.
     */
    protected function cliPrompt(string $field, array $options): string
    {
        return CLI::prompt($field, $options);
    }
}
