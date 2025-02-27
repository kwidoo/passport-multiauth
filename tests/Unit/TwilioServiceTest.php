<?php

namespace Kwidoo\MultiAuth\Tests\Unit;

use Kwidoo\MultiAuth\Services\TwilioService;
use Kwidoo\MultiAuth\Tests\TestCase;
use Mockery;
use Twilio\Rest\Client;
use Twilio\Rest\Verify\V2\ServiceContext\VerificationCheckList;
use Twilio\Rest\Verify\V2\ServiceContext\VerificationList;

class TwilioServiceTest extends TestCase
{
    public function testCreateSendsVerification()
    {
        // Mock Twilio client
        $twilioClientMock = Mockery::mock(Client::class);

        // Mock the VerificationList (the chain ->verifications->create)
        $verificationListMock = Mockery::mock(VerificationList::class);
        $verificationListMock->shouldReceive('create')
            ->once()
            ->with('+1234567890', 'sms')
            ->andReturn(true);

        // Mock the v2 piece and "services($verifySid)"
        $verifyV2Mock = Mockery::mock();
        $verifyV2Mock->shouldReceive('services')
            ->once()
            ->with('test_verify_sid')
            ->andReturn((object)[
                'verifications' => $verificationListMock,
            ]);

        // The "verify" property with v2
        $verifyMock = Mockery::mock();
        $verifyMock->v2 = $verifyV2Mock;

        // Attach to the Twilio client
        $twilioClientMock->verify = $verifyMock;

        // Instantiate our service
        $service = new TwilioService($twilioClientMock);
        $service->create('1234567890'); // sanitize => +1234567890

        $this->assertTrue(true);
    }

    public function testValidateSuccessful()
    {
        $twilioClientMock = Mockery::mock(Client::class);

        // Mock the VerificationCheckList (the chain ->verificationChecks->create)
        $verificationCheckListMock = Mockery::mock(VerificationCheckList::class);
        $verificationCheckListMock->shouldReceive('create')
            ->once()
            ->with([
                'to'   => '+1234567890',
                'code' => '999999',
            ])
            ->andReturn((object)[
                'valid' => true
            ]);

        // Mock the v2 piece and "services($verifySid)"
        $verifyV2Mock = Mockery::mock();
        $verifyV2Mock->shouldReceive('services')
            ->once()
            ->with('test_verify_sid')
            ->andReturn((object)[
                'verificationChecks' => $verificationCheckListMock,
            ]);

        // The "verify" property with v2
        $verifyMock = Mockery::mock();
        $verifyMock->v2 = $verifyV2Mock;

        // Attach to the Twilio client
        $twilioClientMock->verify = $verifyMock;

        // Instantiate our service
        $service = new TwilioService($twilioClientMock);
        $result = $service->validate(['1234567890', '999999']);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        // Always close Mockery
        Mockery::close();
        parent::tearDown();
    }
}
