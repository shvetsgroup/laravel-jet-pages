<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder;

use ShvetsGroup\JetPages\PageBuilder\MenuItem;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MenuItemTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->index = new MenuItem([
            'href' => '/',
            'title' => 'index',
        ]);

        $this->menu1 = new MenuItem([
            'href' => '/menu1',
            'title' => 'menu1',
        ]);
        $this->menu2 = new MenuItem([
            'href' => '/menu2',
            'title' => 'menu2',
        ]);
        $this->index->children = [
            $this->menu1,
            $this->menu2,
        ];

        $this->menu1_1 = new MenuItem([
            'href' => '/menu1/menu1',
            'title' => 'menu1_1',
        ]);
        $this->menu1_2 = new MenuItem([
            'href' => '/menu1/menu2',
            'title' => 'menu1_2',
        ]);
        $this->menu1->children = [
            $this->menu1_1,
            $this->menu1_2,
        ];

        $this->menu2_1 = new MenuItem([
            'href' => '/menu2/menu1',
            'title' => 'menu2_1',
        ]);
        $this->menu2_2 = new MenuItem([
            'href' => '/menu2/menu2',
            'title' => 'menu2_2',
        ]);
        $this->menu2->children = [
            $this->menu2_1,
            $this->menu2_2,
        ];
    }

    public function testGetWithActiveTrailDepth1()
    {
        $active = MenuItem::GetWithActiveTrail($this->index, '/');
        $this->assertNotEquals($active, $this->index);
        $this->assertTrue($active->trail);
        $this->assertEquals('trail active', $active->class);
        $this->assertEquals($active->href, $this->index->href);
        $this->assertEquals($active->title, $this->index->title);
        $this->assertEquals(2, count($active->children));
        $this->assertEquals($active->children[0], $this->menu1);
        $this->assertEquals($active->children[1], $this->menu2);
    }

    public function testGetWithActiveTrailDepth2()
    {
        $active = MenuItem::GetWithActiveTrail($this->index, '/menu1');
        $this->assertNotEquals($active, $this->index);
        $this->assertTrue($active->trail);
        $this->assertEquals('trail', $active->class);
        $this->assertEquals($active->href, $this->index->href);
        $this->assertEquals($active->title, $this->index->title);
        $this->assertEquals(2, count($active->children));
        $this->assertNotEquals($active->children[0], $this->menu1);
        $this->assertEquals($active->children[1], $this->menu2);

        $active1 = $active->children[0];
        $this->assertTrue($active1->trail);
        $this->assertEquals('trail active', $active1->class);
        $this->assertEquals($active1->href, $this->menu1->href);
        $this->assertEquals($active1->title, $this->menu1->title);
        $this->assertEquals(2, count($active1->children));
        $this->assertEquals($active1->children[0], $this->menu1_1);
        $this->assertEquals($active1->children[1], $this->menu1_2);
    }

    public function testGetWithActiveTrailDepth3()
    {
        $active = MenuItem::GetWithActiveTrail($this->index, '/menu2/menu2');
        $this->assertNotEquals($active, $this->index);
        $this->assertTrue($active->trail);
        $this->assertEquals('trail', $active->class);
        $this->assertEquals($active->href, $this->index->href);
        $this->assertEquals($active->title, $this->index->title);
        $this->assertEquals(2, count($active->children));
        $this->assertEquals($active->children[0], $this->menu1);
        $this->assertNotEquals($active->children[1], $this->menu2);

        $active2 = $active->children[1];
        $this->assertTrue($active2->trail);
        $this->assertEquals('trail', $active2->class);
        $this->assertEquals($active2->href, $this->menu2->href);
        $this->assertEquals($active2->title, $this->menu2->title);
        $this->assertEquals(2, count($active2->children));
        $this->assertEquals($active2->children[0], $this->menu2_1);
        $this->assertNotEquals($active2->children[1], $this->menu2_2);

        $active2_2 = $active2->children[1];
        $this->assertTrue($active2_2->trail);
        $this->assertEquals('trail active', $active2_2->class);
        $this->assertEquals($active2_2->href, $this->menu2_2->href);
        $this->assertEquals($active2_2->title, $this->menu2_2->title);
        $this->assertEquals(0, count($active2_2->children));
    }
}