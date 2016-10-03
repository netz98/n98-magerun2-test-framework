# n98-magerun2 Test Framework

This package contains some PHPUnit test cases.

## Prerequisite

The test runner requires a vanilla Magento 2 installation.
To run test suite it's necessary to set the environment variable **N98_MAGERUN2_TEST_MAGENTO_ROOT** with the root path to the installation.

All unit tests run against this installation. Please don't use any production
environment!

## Test Module Commands

Example Test Case:

```php
<?php

namespace Acme\Example\Command;

use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FooCommandTest extends TestCase
{
    /**
     * @test
     */
    public function testOutput()
    {
        /**
         * Load module config for unit test. In this case the relative
         * path from current test case.
         */
        $this->loadConfigFile(__DIR__ . '/../../n98-magerun2.yaml');

        /**
         * Test if command could be found
         */
        $command = $this->getApplication()->find('foo');

        /**
         * Call command
         */
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
            ]
        );
    }
}
```


## Test dev:console Commands

### Code-Generating Command

Create a reference file which contains the content of the code generator.
In the following test case the file is place in "__DIR__ /_files/ExampleSomething.php".

The method `mockWriterFileWriteFileAssertion` of the TestCase class mocks the module writer and compares the output against the reference file.

```php
<?php

namespace Acme\Example\Command\CodeGenerator;

use N98\Magento\Command\Developer\Console\PHPUnit\TestCase;

class MakeSomethingCommandTest extends TestCase
{
    /**
     * @test
     */
    public function testGenerator()
    {
        $command = new MakeSomethingCommand();

        $commandTester = $this->createCommandTester($command);
        $command->setCurrentModuleName('Acme_Example');

        $writerMock = $this->mockWriterFileWriteFileAssertion(
            __DIR__ . '/_files/ExampleSomething.php'
        );

        $command->setCurrentModuleDirectoryWriter($writerMock);
        $commandTester->execute([
            /* pass your parameters */
        ]);
    }
}
```

## Run PHPUnit Tests

```bash
N98_MAGERUN2_TEST_MAGENTO_ROOT=/path/to/magento/root ./vendor/bin/phpunit
```

## Example PHPUnit Configuration

Example module structure:

```plain
.
├── README.md
├── composer.json
├── n98-magerun2.yaml
├── phpunit.xml.dist
├── src
│   └── Command
│       └── ExampleCommand.php
└── tests
    └── Command
        └── ExampleCommandTest.php
```

### phpunit.xml.dist

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false"
         syntaxCheck="false"
         bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="n98-magerun2 Acme Commands">
            <directory suffix="Test.php">tests</directory>
        </testsuite>
    </testsuites>

    <filter>
        <whitelist addUncoveredFilesFromWhitelist="true">
            <directory>src</directory>
        </whitelist>
    </filter>
</phpunit>
```

### composer.json

```json
{
  "name": "acme/example",
  "description": "Some commands",
  "require": {
    "n98/magerun2": "1.2.*"
  },
  "require-dev": {
    "n98/magerun2-test-framework": "dev-master",
    "phpunit/phpunit": "~4.1.0"
  },
  "autoload-dev": {
    "psr-4": {
      "Acme\\Example\\": "tests"
    }
  },
  "autoload": {
    "psr-4": {
      "Acme\\Example\\": "src"
    }
  }
}
```
