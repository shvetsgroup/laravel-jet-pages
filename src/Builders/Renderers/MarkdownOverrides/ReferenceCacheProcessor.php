<?php

namespace ShvetsGroup\JetPages\Builders\Renderers\MarkdownOverrides;

use League\CommonMark\EnvironmentInterface;
use League\CommonMark\Event\DocumentPreParsedEvent;
use League\CommonMark\Reference\Reference;
use League\CommonMark\Reference\ReferenceMap;
use ReflectionClass;

class ReferenceCacheProcessor {
    private $environment;

    /**
     * @var array Custom references map.
     */
    private $referenceMap;

    /**
     * @param  array  $references
     */
    public function setReferences($references)
    {
        $this->referenceMap = new ReferenceMap();
        foreach ($references as $title => $pair) {
            $this->referenceMap->addReference(new Reference($title, $pair['url'], $pair['title']));
        }
    }

    public function __construct(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    public function onDocumentPreParsed(DocumentPreParsedEvent $event)
    {
        $document = $event->getDocument();
        $reflection = new ReflectionClass($document);
        $property = $reflection->getProperty('referenceMap');
        $property->setAccessible(true);
        $property->setValue($document, $this->referenceMap);
    }
}