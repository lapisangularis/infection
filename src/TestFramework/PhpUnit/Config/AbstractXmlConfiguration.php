<?php

declare(strict_types=1);

namespace Infection\TestFramework\PhpUnit\Config;

use Infection\TestFramework\AbstractTestFrameworkAdapter;
use Infection\TestFramework\PhpUnit\Config\Path\PathReplacer;

abstract class AbstractXmlConfiguration
{
    /**
     * @var string
     */
    protected $tempDirectory;

    /**
     * @var string
     */
    protected $originalXmlConfigPath;

    /**
     * @var PathReplacer
     */
    protected $pathReplacer;

    public function __construct(string $tempDirectory, string $originalXmlConfigPath, PathReplacer $pathReplacer)
    {
        $this->tempDirectory = $tempDirectory;
        $this->originalXmlConfigPath = $originalXmlConfigPath;
        $this->pathReplacer = $pathReplacer;
    }

    abstract public function getXml() : string;

    protected function replaceWithAbsolutePaths(\DOMXPath $xPath)
    {
        $queries = [
            '/phpunit/@bootstrap',
            '/phpunit/testsuites/testsuite/exclude',
            '//directory',
            '//file',
        ];

        $nodes = $xPath->query(implode('|', $queries));

        foreach ($nodes as $node) {
            $this->pathReplacer->replaceInNode($node);
        }
    }

    protected function removeExistingLoggers(\DOMDocument $dom, \DOMXPath $xPath)
    {
        $nodes = $xPath->query('/phpunit/logging');

        foreach ($nodes as $node) {
            $dom->documentElement->removeChild($node);
        }
    }

    protected function addLogger(\DOMDocument $dom, \DOMXPath $xPath)
    {
        $loggingList = $xPath->query('/phpunit/logging');

        if ($loggingList->length) {
            $logging = $loggingList->item(0);
        } else {
            $logging = $dom->createElement('logging');
            $dom->documentElement->appendChild($logging);
        }

        $log = $dom->createElement('log');
        $log->setAttribute('type', 'coverage-php');
        $log->setAttribute('target', $this->tempDirectory . '/' . AbstractTestFrameworkAdapter::COVERAGE_FILE_NAME);

        $logging->appendChild($log);
    }

    protected function setStopOnFailure(\DOMXPath $xPath)
    {
        $nodeList = $xPath->query('/phpunit/@stopOnFailure');

        if ($nodeList->length) {
            $nodeList[0]->nodeValue = 'true';
        } else {
            $node = $xPath->query('/phpunit')[0];
            $node->setAttribute('stopOnFailure', 'true');
        }
    }
}