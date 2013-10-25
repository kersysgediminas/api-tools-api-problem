<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ApiProblem\Listener;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ApiProblem\Exception\DomainException;
use ZF\ApiProblem\Listener\SendApiProblemResponseListener;

class SendApiProblemResponseListenerTest extends TestCase
{
    public function setUp()
    {
        $this->exception  = new DomainException('Random error', 400);
        $this->apiProblem = new ApiProblem(400, $this->exception);
        $this->response   = new ApiProblemResponse($this->apiProblem);
        $this->event      = new SendResponseEvent();
        $this->event->setResponse($this->response);
        $this->listener   = new SendApiProblemResponseListener();
    }

    public function testListenerImplementsResponseSenderInterface()
    {
        $this->assertInstanceOf('Zend\Mvc\ResponseSender\ResponseSenderInterface', $this->listener);
    }

    public function testDisplayExceptionsFlagIsFalseByDefault()
    {
        $this->assertFalse($this->listener->displayExceptions());
    }

    /**
     * @depends testDisplayExceptionsFlagIsFalseByDefault
     */
    public function testDisplayExceptionsFlagIsMutable()
    {
        $this->listener->setDisplayExceptions(true);
        $this->assertTrue($this->listener->displayExceptions());
    }

    /**
     * @depends testDisplayExceptionsFlagIsFalseByDefault
     */
    public function testSendContentDoesNotRenderExceptionsByDefault()
    {
        ob_start();
        $this->listener->sendContent($this->event);
        $contents = ob_get_clean();
        $this->assertInternalType('string', $contents);
        $data = json_decode($contents, true);
        $this->assertNotContains("\n", $data['detail']);
        $this->assertNotContains($this->exception->getTraceAsString(), $data['detail']);
    }

    public function testEnablingDisplayExceptionFlagRendersExceptionStackTrace()
    {
        $this->listener->setDisplayExceptions(true);
        ob_start();
        $this->listener->sendContent($this->event);
        $contents = ob_get_clean();
        $this->assertInternalType('string', $contents);
        $data = json_decode($contents, true);
        $this->assertContains("\n", $data['detail']);
        $this->assertContains($this->exception->getTraceAsString(), $data['detail']);
    }

    public function testSendContentDoesNothingIfEventDoesNotContainApiProblemResponse()
    {
        $this->event->setResponse(new HttpResponse);
        ob_start();
        $this->listener->sendContent($this->event);
        $contents = ob_get_clean();
        $this->assertInternalType('string', $contents);
        $this->assertEmpty($contents);
    }
}
