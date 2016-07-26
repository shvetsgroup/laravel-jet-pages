<?php namespace ShvetsGroup\JetPages\Page;

class SlugIsRequired extends \Exception
{
    protected $message = "Page requires a slug field.";
}