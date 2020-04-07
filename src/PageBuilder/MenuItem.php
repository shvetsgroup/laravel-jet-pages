<?php

namespace ShvetsGroup\JetPages\PageBuilder;

class MenuItem
{
    public $href;
    public $title;
    public $icon;
    public $class;
    public $fragment;

    /**
     * @var MenuItem[]
     */
    public $children = [];

    public function __construct($attributes = [])
    {
        if (isset($attributes['children'])) {
            $children = $attributes['children'];
            unset($attributes['children']);
            foreach ($children as $child) {
                $this->children[] = new static($child);
            }
        }
        $this->fillFromArray($attributes);
    }

    public function fillFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function GetWithActiveTrail(MenuItem $menu, $href)
    {
        if (!isset($menu->href) && empty($menu->children)) {
            return $menu;
        }

        if ($menu->href == $href) {
            $clone = clone $menu;
            $clone->trail = true;
            $clone->class = $clone->class ?? '';
            $clone->class = trim($clone->class.' trail active');
            return $clone;
        }

        if (isset($menu->children)) {
            foreach ($menu->children as $key => $child) {
                $cloneChild = MenuItem::GetWithActiveTrail($child, $href);
                if ($child !== $cloneChild) {
                    $clone = clone $menu;

                    $clone->trail = true;
                    $clone->class = $clone->class ?? '';
                    $clone->class = trim($clone->class.' trail');
                    $clone->children[$key] = $cloneChild;

                    return $clone;
                }
            }
        }

        return $menu;
    }

    public function toArray()
    {
        $items = [
            'href' => $this->href,
            'title' => $this->title,
            'icon' => $this->icon,
            'class' => $this->class,
            'fragment' => $this->fragment,
        ];
        $items = array_filter($items);
        $children = [];
        foreach ($this->children as $child) {
            $children[] = $child->toArray();
        }
        if (!empty($children)) {
            $items['children'] = $children;
        }
        return $items;
    }
}