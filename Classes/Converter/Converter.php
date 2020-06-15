<?php

namespace Walther\Html2pdf\Converter;

use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use Walther\Html2pdf\Configuration\Configuration;

/**
 * Class Converter
 *
 * @package Walther\Html2pdf\Converter
 */
class Converter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * Converter constructor.
     *
     * @param \TYPO3\CMS\Core\Localization\LanguageService $languageService
     */
     public function __construct(LanguageService $languageService = NULL)
     {
         $this->languageService = $languageService;
         if ($this->languageService) {
             $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
         }
     }

     /**
      * @param \TYPO3\CMS\Core\Localization\LanguageService $languageService
      *
      * @return void
      */
     public function injectLanguageService(LanguageService $languageService) : void
     {
         if (!$this->languageService) {
             $this->languageService = $languageService;
             if ($this->languageService) {
                 $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
             }
         }
     }

    /**
     * convert
     *
     * @param       $input
     * @param array $options
     *
     * @return bool|string
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function convert($input, $options = [])
    {
        $binary = Configuration::getBinaryPath();

        if (empty($binary)) {
            throw new InvalidArgumentException($this->languageService->getLL('html2pdf.converter.binary.error'));
        }

        $cmd = sprintf('%s %s - -', escapeshellcmd($binary), $this->formatBinaryOptions($options));

        $specs = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $pipes = [];

        $process = proc_open($cmd, $specs, $pipes);

        $stdout = '';
        $stderr = '';
        $returnCode = 0;

        if (is_resource($process)) {

            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // 2 => readable handle connected to child stderr

            fwrite($pipes[0], $this->makeAbsoluteURLs($input));
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $returnCode = proc_close($process);
        }

        if ($returnCode > 1) {
            // usually thrown when an invalid binary is called
            throw new RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.returnCode') . $returnCode);
        }

        $stderrL = strtolower($stderr);

        if (strpos($stderrL, 'error') !== false || strpos($stderrL, 'unknown') !== false) {
            $this->logger->error($this->languageService->getLL('html2pdf.converter.generator.error.stderr') . '<pre>' . $stderr . '</pre>');
            throw new RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.stderr'));
        }

        if (trim($stdout) === '') {
            $this->logger->error($this->languageService->getLL('html2pdf.converter.generator.error.stdout') . '<pre>' . $stderr . '</pre>');
            throw new RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.stdout'));
        }

        return $stdout;
    }

    /**
     * formatBinaryOptions
     *
     * format and escape all options for the binary call
     *
     * @param $options
     *
     * @return string
     */
    protected function formatBinaryOptions($options) : string
    {
        $return = [];

        if (is_array($options)) {
            foreach ($options as $name => $value) {
                if ($value !== '') {
                    $return[] = sprintf('--%s %s', escapeshellcmd($name), escapeshellarg($value));
                } else {
                    $return[] = '--' . escapeshellcmd($name);
                }
            }
        }

        return implode(' ', $return);
    }

    /**
     * makeAbsoluteURLs
     *
     * add absolute urls to the html output
     *
     * @param string $html
     *
     * @return string
     */
    protected function makeAbsoluteURLs(string $html) : string
    {
        if (is_null($html)) {
            return false;
        }

        /** @var \TYPO3\CMS\Core\Http\ServerRequest $serverRequest */
        $serverRequest = $GLOBALS['TYPO3_REQUEST'];
        $baseURL = $serverRequest->getUri()->getScheme() . '://' . $serverRequest->getUri()->getHost();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($dom);

        // add base if not exists
        $baseUrlTag = $dom->getElementsByTagName('base');
        if ($baseUrlTag->length === 0) {
            $head = $dom->getElementsByTagName('head')->item(0);
            if ($head) {
                $base = $dom->createElement('base');
                $base->setAttribute('href', $baseURL . '/');
                if ($head->hasChildNodes()) {
                    $head->insertBefore($base, $head->firstChild);
                } else {
                    $head->appendChild($base);
                }
            }
        }

        // Method #1 - Nested Xpath Queries:
        $tagsAndAttributes = [
            'link' => 'href',
            'script' => 'src',
            'img' => 'src',
            'form' => 'action',
            'a' => 'href'
        ];

        foreach ($tagsAndAttributes as $tag => $attr) {
            foreach ($xpath->query("//{$tag}[not(starts-with(@{$attr}, 'http'))]") as $node) {
                $node->setAttribute($attr, $baseURL . $node->getAttribute($attr));
            }
        }

        // Method #2 - Single Xpath Query w/ Condition Block
        $targets = [
            "//img[not(starts-with(@src, 'http'))]",
            "//form[not(starts-with(@action, 'http'))]",
            "//a[not(starts-with(@href, 'http'))]"
        ];

        foreach ($xpath->query(implode('|', $targets)) as $node) {
            if ($src = $node->getAttribute('src')) {
                $node->setAttribute('src', $baseURL . $src);
            } elseif ($action = $node->getAttribute('action')) {
                $node->setAttribute('action', $baseURL . $action);
            } else {
                $node->setAttribute('href', $baseURL . $node->getAttribute('href'));
            }
        }

        return $dom->saveHTML();
    }
}
