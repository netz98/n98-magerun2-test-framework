<?php

namespace N98\Magento\Command\Developer\Console\PHPUnit;

use Magento\Framework\Filesystem\Directory\WriteInterface;
use N98\Magento\Command\Developer\Console\AbstractConsoleCommand;
use N98\Magento\Command\PHPUnit\TestCase as BaseTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psy\Context;
use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param AbstractConsoleCommand $command
     * @return CommandTester
     */
    public function createCommandTester(AbstractConsoleCommand $command)
    {
        $di = $this->getApplication()->getObjectManager();

        $command->setContext(new Context());
        $command->setScopeVariable('di', $di);

        $commandTester = new CommandTester($command);

        return $commandTester;
    }

    /**
     * @param $referenceFilePath
     * @param \PHPUnit_Framework_MockObject_MockObject|null $writerMock
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation|null $matcher
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockWriterFileWriteFileAssertion(
        $referenceFilePath,
        \PHPUnit_Framework_MockObject_MockObject $writerMock = null,
        \PHPUnit_Framework_MockObject_Matcher_Invocation $matcher = null
    ) {
        if ($writerMock === null) {
            $writerMock = $this->getMockBuilder(WriteInterface::class)->getMock();
        }

        if ($matcher === null) {
            $matcher = $this->once();
        }

        $writerMock
            ->expects($matcher)
            ->method('writeFile')
            ->with(
                $this->anything(), // param1
                $this->callback(function ($subject) use ($referenceFilePath) {
                    // apply cs-fixes as the code generator is a mess
                    $replacements = [
                        // empty class/interface
                        '~\{\n\n\n\}\n\n$~' => "{\n}\n",
                        // fix end of file for class w content
                        '~    \}\n\n\n\}\n\n$~' => "    }\n}\n",
                        // fix beginning of class
                        '~^(class .*)\n{\n\n~m' => "\\1\n{\n",
                    ];
                    $buffer = preg_replace(array_keys($replacements), $replacements, $subject);
                    $expected = file_get_contents($referenceFilePath);

                    $this->assertEquals($expected, $buffer);

                    return $buffer === $expected;
                })
            );

        return $writerMock;
    }
}
