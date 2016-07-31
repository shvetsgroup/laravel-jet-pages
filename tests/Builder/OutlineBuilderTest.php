<?php namespace ShvetsGroup\Tests\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\OutlineBuilder;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class OutlineBuilderTest extends AbstractTestCase
{
    /**
     * @var OutlineBuilder
     */
    private $outline;

    public function setUp()
    {
        parent::setUp();
        $this->outline = app()->make(OutlineBuilder::class);
    }

    public function testGetRawOutline()
    {
        $raw_outline = $this->outline->getRawOutline();
        $this->assertEquals(['index' => 1, 'test' => ['test/test' => 1]], $raw_outline);
    }

    public function testGetRawOutlineMissing()
    {
        $this->assertNull($this->outline->getRawOutline('non-existent-path'));
    }

    public function testGetFlatOutline()
    {
        $raw_outline = $this->outline->getFlatOutline();
        $this->assertEquals(['index' => 1, 'test' => 1, 'test/test' => 2], $raw_outline);
    }
}
