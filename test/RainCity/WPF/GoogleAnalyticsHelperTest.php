<?php
namespace RainCity\WPF;

use PHPUnit\Framework\TestCase;
use RainCity\TestHelper\ReflectionHelper;
use TheIconic\Tracking\GoogleAnalytics\Analytics;
use TheIconic\Tracking\GoogleAnalytics\AnalyticsResponse;

class GoogleAnalyticsHelperTest extends TestCase
{
    const TEST_TRACKING_ID = 'UA-9A8B7C6D-99';
    const TEST_CLIENT_ID = 'TestClientId';
    const TEST_GOOGLE_COOKIE = 'testGoogleCookie';

    const TEST_CATEGORY = 'testCategory';
    const TEST_ACTION = 'testAction';
    const TEST_LABEL = 'testLabel';

    const CALL_METHOD = '__call';

    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        unset($_COOKIE[GoogleAnalyticsHelper::GOOGLE_COOKIE_NAME]);
    }

    public function testCtor_emptyTrackingId() {
        $this->expectException("InvalidArgumentException");

        new GoogleAnalyticsHelper('');    // NOSONAR
    }

    public function testCtor_blankTrackingId() {
        $this->expectException("InvalidArgumentException");

        new GoogleAnalyticsHelper('  ');    // NOSONAR
    }

    public function testCtor_defaults() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->assertNotNull($obj);
        $this->assertInstanceOf(GoogleAnalyticsHelper::class, $obj);

        /** @var Analytics */
        $analyticsObj = ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'analytics', $obj);

        $this->assertNotNull($analyticsObj);
        $this->assertInstanceOf(Analytics::class, $analyticsObj);

        $this->assertEquals('1', $analyticsObj->getProtocolVersion());
        $this->assertEquals(self::TEST_TRACKING_ID, $analyticsObj->getTrackingId());
        $this->assertEquals(GoogleAnalyticsHelper::UNKNOWN_CLIENT_IDENTIFIER, $analyticsObj->getClientId());
    }

    public function testCtor_googleCookie() {
        $_COOKIE[GoogleAnalyticsHelper::GOOGLE_COOKIE_NAME] = self::TEST_GOOGLE_COOKIE;

        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->assertNotNull($obj);
        $this->assertInstanceOf(GoogleAnalyticsHelper::class, $obj);

        /** @var Analytics */
        $analyticsObj = ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'analytics', $obj);

        $this->assertNotNull($analyticsObj);
        $this->assertInstanceOf(Analytics::class, $analyticsObj);

        $this->assertEquals('1', $analyticsObj->getProtocolVersion());
        $this->assertEquals(self::TEST_TRACKING_ID, $analyticsObj->getTrackingId());
        $this->assertEquals(self::TEST_GOOGLE_COOKIE, $analyticsObj->getClientId());
    }

    public function testSetClientId_emptyClientId() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        /** @var Analytics */
        $analyticsObj = ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'analytics', $obj);

        $this->assertEquals(GoogleAnalyticsHelper::UNKNOWN_CLIENT_IDENTIFIER, $analyticsObj->getClientId());

        $this->expectException(\InvalidArgumentException::class);

        $obj->setClientId('');
    }

    public function testSetClientId_blankClientId() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        /** @var Analytics */
        $analyticsObj = ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'analytics', $obj);

        $this->assertEquals(GoogleAnalyticsHelper::UNKNOWN_CLIENT_IDENTIFIER, $analyticsObj->getClientId());

        $this->expectException(\InvalidArgumentException::class);

        $obj->setClientId('    ');
    }

    public function testSetClientId() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $obj->setClientId(self::TEST_CLIENT_ID);

        /** @var Analytics */
        $analyticsObj = ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'analytics', $obj);

        $this->assertEquals(self::TEST_CLIENT_ID, $analyticsObj->getClientId());
    }

    public function testSetUserRemoteIP() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        // verify default state
        $this->assertTrue(ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'useRemoteIp', $obj));

        $obj->setUseRemoteIP(false);

        $this->assertFalse(ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'useRemoteIp', $obj));

        $obj->setUseRemoteIP(true);

        $this->assertTrue(ReflectionHelper::getObjectProperty(GoogleAnalyticsHelper::class, 'useRemoteIp', $obj));
    }

    public function testSendEvent_badCategory() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->expectException(\InvalidArgumentException::class);

        $obj->sendEvent('', '', '');
    }

    public function testSendEvent_badAction() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->expectException(\InvalidArgumentException::class);

        $obj->sendEvent(self::TEST_CATEGORY, '', '');
    }

    public function testSendEvent_badLabel() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->expectException(\InvalidArgumentException::class);

        $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, '');
    }

    public function testSendEvent_badValue() {
        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);

        $this->expectException(\InvalidArgumentException::class);

        $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, self::TEST_LABEL, -1);
    }

    public function testSendEvent_noValueNoRemoteIP() {
        $mockAnalytics = $this->createMock(Analytics::class);
        $mockResponse = $this->createMock(AnalyticsResponse::class);

        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);
        $obj->setAnalytics($mockAnalytics);
        $obj->setUseRemoteIP(false);

        /*
         * Expectation: the magic '__call' method will be called four times,
         * with one of the paramters sets in withConsecutive() and on each
         * return the next value from willReturnOnConsecutiveCalls().
         */
        $mockAnalytics
            ->expects($this->exactly(4))
            ->method(CALL_METHOD)
            ->withConsecutive(
                array('setEventCategory', array(self::TEST_CATEGORY)),
                array('setEventAction', array(self::TEST_ACTION)),
                array('setEventLabel', array(self::TEST_LABEL)),
                array('sendEvent', array())
                )
        ->willReturnOnConsecutiveCalls(
            $mockAnalytics,
            $mockAnalytics,
            $mockAnalytics,
            $mockResponse
            );

        // When the getHttpStatusCode method is called on the response, return 200
        $mockResponse
            ->expects($this->once())
            ->method('getHttpStatusCode')
            ->willReturn(200);

        $result = $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, self::TEST_LABEL);

        $this->assertTrue($result);
    }

    public function testSendEvent_withValue() {
        $mockAnalytics = $this->createMock(Analytics::class);
        $mockResponse = $this->createMock(AnalyticsResponse::class);

        $testValue = rand(0, 1000);

        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);
        $obj->setAnalytics($mockAnalytics);
        $obj->setUseRemoteIP(false);

        /*
         * Expectation: the magic '__call' method will be called four times,
         * with one of the paramters sets in withConsecutive() and on each
         * return the next value from willReturnOnConsecutiveCalls().
         */
        $mockAnalytics
        ->expects($this->exactly(5))
        ->method(CALL_METHOD)
        ->withConsecutive(
            array('setEventCategory', array(self::TEST_CATEGORY)),
            array('setEventAction', array(self::TEST_ACTION)),
            array('setEventLabel', array(self::TEST_LABEL)),
            array('setEventValue', array($testValue)),
            array('sendEvent', array())
            )
            ->willReturnOnConsecutiveCalls(
                $mockAnalytics,
                $mockAnalytics,
                $mockAnalytics,
                $mockAnalytics,
                $mockResponse
                );

            // When the getHttpStatusCode method is called on the response, return 200
            $mockResponse
            ->expects($this->once())
            ->method('getHttpStatusCode')
            ->willReturn(200);

            $result = $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, self::TEST_LABEL, $testValue);

            $this->assertTrue($result);
    }

    public function testSendEvent_withRemoteIP() {
        $mockAnalytics = $this->createMock(Analytics::class);
        $mockResponse = $this->createMock(AnalyticsResponse::class);

        $_SERVER['REMOTE_ADDR'] = '192.168.9.10';

        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);
        $obj->setAnalytics($mockAnalytics);

        /*
         * Expectation: the magic '__call' method will be called four times,
         * with one of the paramters sets in withConsecutive() and on each
         * return the next value from willReturnOnConsecutiveCalls().
         */
        $mockAnalytics
        ->expects($this->exactly(5))
        ->method(CALL_METHOD)
        ->withConsecutive(
            array('setEventCategory', array(self::TEST_CATEGORY)),
            array('setEventAction', array(self::TEST_ACTION)),
            array('setEventLabel', array(self::TEST_LABEL)),
            array('setIpOverride', array($_SERVER['REMOTE_ADDR'])),
            array('sendEvent', array())
            )
            ->willReturnOnConsecutiveCalls(
                $mockAnalytics,
                $mockAnalytics,
                $mockAnalytics,
                $mockAnalytics,
                $mockResponse
                );

            // When the getHttpStatusCode method is called on the response, return 200
            $mockResponse
            ->expects($this->once())
            ->method('getHttpStatusCode')
            ->willReturn(200);

            $result = $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, self::TEST_LABEL);

            $this->assertTrue($result);
    }


    public function testSendEvent_responseError() {
        $mockAnalytics = $this->createMock(Analytics::class);
        $mockResponse = $this->createMock(AnalyticsResponse::class);

        $obj = new GoogleAnalyticsHelper(self::TEST_TRACKING_ID);
        $obj->setAnalytics($mockAnalytics);
        $obj->setUseRemoteIP(false);

        /*
         * Expectation: the magic '__call' method will be called four times,
         * with one of the paramters sets in withConsecutive() and on each
         * return the next value from willReturnOnConsecutiveCalls().
         */
        $mockAnalytics
            ->expects($this->exactly(4))
            ->method(CALL_METHOD)
            ->withConsecutive(
                array('setEventCategory', array(self::TEST_CATEGORY)),
                array('setEventAction', array(self::TEST_ACTION)),
                array('setEventLabel', array(self::TEST_LABEL)),
                array('sendEvent', array())
                )
        ->willReturnOnConsecutiveCalls(
            $mockAnalytics,
            $mockAnalytics,
            $mockAnalytics,
            $mockResponse
            );

        // When the getHttpStatusCode method is called on the response, return 200
        $mockResponse
            ->expects($this->once())
            ->method('getHttpStatusCode')
            ->willReturn(401);

        $result = $obj->sendEvent(self::TEST_CATEGORY, self::TEST_ACTION, self::TEST_LABEL);

        $this->assertFalse($result);
    }
}
