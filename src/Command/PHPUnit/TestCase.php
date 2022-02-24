<?php

namespace N98\Magento\Command\PHPUnit;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use N98\Magento\Application;
use N98\Magento\Application\ConfigFile;
use N98\Util\ArrayFunctions;
use PHPUnit_Framework_MockObject_MockObject;
use RuntimeException;

/**
 * Class TestCase
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\PHPUnit
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $application = null;

    /**
     * @var string|null
     */
    private $root;

    /**
     * Apply additional YAML config
     *
     * @param string $configPath Path to YAML config file
     * @return void
     */
    public function loadConfigFile($configPath)
    {
        $config = $this->getApplication()->getConfig();
        $additionalConfig = ConfigFile::createFromFile($configPath)->toArray();
        $mergedConfig = ArrayFunctions::mergeArrays($config, $additionalConfig);
        $this->getApplication()->reinit($mergedConfig);
    }

    /**
     * @param string $varname name of the environment variable containing the test-root
     * @param string $basename name of the stopfile containing the test-root
     *
     * @return string|null
     */
    public static function getTestMagentoRootFromEnvironment($varname, $basename)
    {
        $root = getenv($varname);
        if (empty($root) && strlen($basename)) {
            $stopfile = getcwd() . '/' . $basename;
            if (is_readable($stopfile) && $buffer = rtrim(file_get_contents($stopfile))) {
                $root = $buffer;
            }
        }
        if (empty($root)) {
            return;
        }

        # directory test
        if (!is_dir($root)) {
            throw new RuntimeException(
                sprintf("%s path '%s' is not a directory", $varname, $root)
            );
        }

        # resolve root to realpath to be independent to current working directory
        $rootRealpath = realpath($root);
        if (false === $rootRealpath) {
            throw new RuntimeException(
                sprintf("Failed to resolve %s path '%s' with realpath()", $varname, $root)
            );
        }

        return $rootRealpath;
    }

    /**
     * getter for the magento root directory of the test-suite
     *
     * @see ApplicationTest::testExecute
     *
     * @return string
     */
    public function getTestMagentoRoot()
    {
        if ($this->root) {
            return $this->root;
        }

        $varname = 'N98_MAGERUN2_TEST_MAGENTO_ROOT';
        $basename = '.n98-magerun2';

        $root = self::getTestMagentoRootFromEnvironment($varname, $basename);

        if (null === $root) {
            $this->markTestSkipped(
                "Please specify environment variable $varname with path to your test magento installation!"
            );
        }

        return $this->root = $root;
    }

    /**
     * @return Application|PHPUnit_Framework_MockObject_MockObject
     */
    public function getApplication()
    {
        if ($this->application === null) {
            $root = $this->getTestMagentoRoot();

            /** @var Application|PHPUnit_Framework_MockObject_MockObject $application */
            $application = $this->getMock('N98\Magento\Application', array('getMagentoRootFolder'));
            $loader = require __DIR__ . '/../../../../../../vendor/autoload.php';
            $application->setAutoloader($loader);
            $application->expects($this->any())->method('getMagentoRootFolder')->will($this->returnValue($root));
            $application->init();
            $application->initMagento();

            $this->application = $application;
        }

        return $this->application;
    }

    /**
     * @return AdapterInterface
     */
    public function getDatabaseConnection()
    {
        $resource = $this->getApplication()->getObjectManager()->get(ResourceConnection::class);

        return $resource->getConnection('write');
    }
}
