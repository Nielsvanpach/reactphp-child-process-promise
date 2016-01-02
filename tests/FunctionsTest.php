<?php

namespace WyriHaximus\React\Tests;

use Evenement\EventEmitter;
use Phake;
use React\EventLoop\Factory;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testChildProcessPromise()
    {
        $loop = Factory::create();
        $process = Phake::partialMock('React\ChildProcess\Process', [
            'uptime',
        ]);
        $process->stderr = new EventEmitter();
        $process->stdout = new EventEmitter();
        Phake::when($process)->start($loop)->thenReturnCallback(function () use ($process, $loop) {
            \WyriHaximus\React\futurePromise($loop, $process)->then(function ($process) {
                $process->stderr->emit('data', ['abc']);
                $process->stdout->emit('data', ['def']);
                $process->emit('exit', [123]);
            });
        });

        $called = false;
        \WyriHaximus\React\childProcessPromise($loop, $process)->then(function ($result) use (&$called) {
            $this->assertEquals([
                'buffers' => [
                    'stderr' => 'abc',
                    'stdout' => 'def',
                ],
                'exitCode' => 123,
            ], $result);
            $called = true;
        });
        $loop->run();
        $this->assertTrue($called);
    }
}