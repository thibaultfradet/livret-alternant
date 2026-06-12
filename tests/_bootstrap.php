<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require_once dirname(__DIR__).'/vendor/autoload.php';

// Force environment to test for tests
$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = false;

new Dotenv('APP_ENV', 'APP_DEBUG')->bootEnv(dirname(__DIR__).'/.env.test');


// Clean up from previous runs
try {
    new Filesystem()->remove([__DIR__ . '/../var/cache/test']);
} catch (\Exception $e) {
    echo "Erreur suppression cache : " . $e->getMessage() . PHP_EOL;
}
new Filesystem()->remove([__DIR__ . '/../var/sessions/test']);

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$output = new ConsoleOutput();
$application = new Application($kernel);
$application->setAutoExit(false);
$application->setCatchExceptions(false);

$runCommand = static function (string $name, array $options = []) use ($application): void {
    $input = new ArrayInput(array_merge(['command' => $name], $options));
    $input->setInteractive(false);
    $application->run($input);
};

$runCommand('doctrine:database:drop', [
    '--force' => true,
    '--if-exists' => true,
]);

$runCommand('doctrine:database:create', [
    '--if-not-exists' => true,
]);
$runCommand('doctrine:schema:create');
$runCommand('doctrine:fixtures:load', [
    '--group' => ['CodeceptionFixtures'],
    '--no-interaction' => true,
]);

$kernel->shutdown();