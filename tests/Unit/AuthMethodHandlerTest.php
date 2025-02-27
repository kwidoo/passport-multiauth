<?php

namespace Kwidoo\MultiAuth\Tests\Unit;

use Kwidoo\MultiAuth\AuthMethodHandler;
use Kwidoo\MultiAuth\Contracts\AuthStrategy;
use Kwidoo\MultiAuth\Tests\TestCase;
use Mockery;

class AuthMethodHandlerTest extends TestCase
{
    public function testValidateCallsCorrectStrategy()
    {
        // Arrange
        $mockStrategy = Mockery::mock(AuthStrategy::class);
        $mockStrategy->shouldReceive('validate')
            ->once()
            ->with(['test-credentials'])
            ->andReturn(true);

        $handler = new AuthMethodHandler(['mock' => $mockStrategy]);

        // Act
        $result = $handler->validate('mock', ['test-credentials']);

        // Assert
        $this->assertTrue($result);
    }

    public function testValidateThrowsOnUnsupportedMethod()
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = new AuthMethodHandler([]);
        $handler->validate('does_not_exist', []);
    }
}
