<?php

namespace ShvetsGroup\JetPages\Builders\Renderers\MarkdownOverrides;

use League\CommonMark\Block\Element\Document;
use League\CommonMark\Reference\ReferenceMap;

/**
 * Exact copy of CustomDocument with ability to override referencemap.
 */
class CustomDocument extends Document
{
    public function __construct($referenceMap = null)
    {
        parent::__construct();

        $this->setStartLine(1);

        if ($referenceMap) {
            $this->referenceMap = $referenceMap;
        } else {
            $this->referenceMap = new ReferenceMap();
        }
    }
}
