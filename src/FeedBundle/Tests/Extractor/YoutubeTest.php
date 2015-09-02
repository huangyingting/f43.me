<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Youtube;
use GuzzleHttp\Exception\RequestException;

class YoutubeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://www.youtube.com/watch?v=UacN1xwVK2Y', true),
            array('http://youtube.com/watch?v=UacN1xwVK2Y', true),
            array('https://youtu.be/UacN1xwVK2Y', true),
            array('http://youtu.be/UacN1xwVK2Y?t=1s', true),
            array('https://goog.co', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $youtube = new Youtube($guzzle);
        $this->assertEquals($expected, $youtube->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContent()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $youtube = new Youtube($guzzle);

        // first test fail because we didn't match an url, so YoutubeUrl isn't defined
        $this->assertEmpty($youtube->getContent());

        $youtube->match('https://www.youtube.com/watch?v=UacN1xwVK2Y');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $youtube->getContent());
        // this one will got an empty array
        $this->assertEmpty($youtube->getContent());
        // this one will catch an exception
        $this->assertEmpty($youtube->getContent());
    }
}