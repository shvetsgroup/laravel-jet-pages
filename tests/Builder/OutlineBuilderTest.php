<?php

namespace ShvetsGroup\Tests\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class OutlineBuilderTest extends AbstractTestCase
{
    /**
     * @var Outline
     */
    private $outline;

    public function setUp(): void
    {
        parent::setUp();
        $this->linkFixtureContent();
        $this->outline = app()->make(Outline::class);
    }

    public function testGetRawOutline()
    {
        $raw_outline = $this->outline->getRawOutline();
        $this->assertEquals(['index' => 1, 'test' => ['test/test' => 1]], $raw_outline);
    }

    public function testGetRawOutlineMissing()
    {
        $raw_outline = $this->outline->getRawOutline('non-existent-path');
        $this->assertEquals(['index' => 1, 'test' => ['test/test' => 1]], $raw_outline);
    }

    public function testGetFlatOutline()
    {
        $raw_outline = $this->outline->getFlatOutline();
        $this->assertEquals(['index' => 1, 'test' => 1, 'test/test' => 2], $raw_outline);
    }
}
