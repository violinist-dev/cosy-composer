<?php

namespace eiriksm\CosyComposerTest\integration;

use Composer\Console\Application;
use eiriksm\ArrayOutput\ArrayOutput;
use eiriksm\CosyComposer\CommandExecuter;
use eiriksm\CosyComposer\CosyComposer;
use eiriksm\CosyComposerTest\GetCosyTrait;
use eiriksm\CosyComposerTest\GetExecuterTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputDefinition;

abstract class Base extends TestCase
{
    use GetCosyTrait;
    use GetExecuterTrait;

    protected function createExpectedCommandForPackage($package)
    {
        return "composer update -n --no-ansi $package --with-dependencies";
    }

    protected function createUpdateJsonFromData($package, $version, $new_version)
    {
        return sprintf('{"installed": [{"name": "%s", "version": "%s", "latest": "%s", "latest-status": "semver-safe-update"}]}', $package, $version, $new_version);
    }

    protected function createComposerFileFromFixtures($dir, $filename)
    {
        $composer_contents = file_get_contents(__DIR__ . "/../fixtures/$filename");
        $composer_file = "$dir/composer.json";
        file_put_contents($composer_file, $composer_contents);
    }

    protected function setupDirectory(CosyComposer $c, $directory)
    {
        mkdir($directory);
        $c->setTmpDir($directory);
    }

    protected function getMockDefinition()
    {
        // Create a mock app, that can respond to things.
        $mock_definition = $this->createMock(InputDefinition::class);
        $mock_definition->method('getOptions')
            ->willReturn([]);
        return $mock_definition;
    }

    protected function getMockApp($mock_definition)
    {
        $mock_app = $this->createMock(Application::class);
        $mock_app->method('getDefinition')
            ->willReturn($mock_definition);
        return $mock_app;
    }

    protected function getMockOutputWithUpdate($package, $version_from, $version_to)
    {
        $mock_output = $this->createMock(ArrayOutput::class);
        $mock_output->method('fetch')
            ->willReturn([
                [
                    $this->createUpdateJsonFromData($package, $version_from, $version_to),
                ]
            ]);
        return $mock_output;
    }
}
