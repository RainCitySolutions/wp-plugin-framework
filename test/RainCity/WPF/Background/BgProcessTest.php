<?php
namespace RainCity\WPF\Background;

use RainCity\TestHelper\ReflectionHelper;
use RainCity\WPF\Helpers\WordpressTestCase;

/**
 *
 * @covers \RainCity\WPF\Background\BgProcess
 *
 */
class BgProcessTest extends WordpressTestCase
{

    public function testParamsPassedToTask_two()
    {
        $testParam1 = 'a';
        $testParam2 = 1;

        $bgp = new BgProcess($testParam1, $testParam2);

        $mockTask = $this->createMock(BgTask::class);
        $mockTask
            ->expects(self::once())
            ->method('run')
            ->with(
                $bgp,
                $testParam1,
                $testParam2
                )
            ->willReturn(true);

        $result = ReflectionHelper::invokeObjectMethod(BgProcess::class, $bgp, 'task', $mockTask);

        $this->assertFalse($result);
    }

    public function testParamsPassedToTask_four()
    {
        $testParam1 = 'cat';
        $testParam2 = array(5,4,3,2,1);
        $testParam3 = 513;
        $testParam4 = 'dog';

        $bgp = new BgProcess($testParam1, $testParam2, $testParam3, $testParam4);

        $mockTask = $this->createMock(BgTask::class);
        $mockTask
        ->expects(self::once())
        ->method('run')
        ->with(
            $bgp,
            $testParam1,
            $testParam2,
            $testParam3,
            $testParam4,
            )
            ->willReturn(true);

            $result = ReflectionHelper::invokeObjectMethod(BgProcess::class, $bgp, 'task', $mockTask);

            $this->assertFalse($result);
    }
}
