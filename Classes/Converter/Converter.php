<?php

namespace Walther\Html2pdf\Converter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;

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
     * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
     */
    protected $basicFileUtility;

    /**
     * Converter constructor.
     *
     * @param \TYPO3\CMS\Core\Localization\LanguageService $languageService
     */
    public function __construct(\TYPO3\CMS\Core\Localization\LanguageService $languageService, BasicFileUtility $basicFileUtility)
    {
        $this->languageService = $languageService;
        $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');

        $this->basicFileUtility = $basicFileUtility;
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
        $binary = \Walther\Html2pdf\Configuration\Configuration::getBinaryPath();

        if (empty($binary)) {
            throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException($this->languageService->getLL('html2pdf.converter.binary.error'));
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

            fwrite($pipes[0], $input);
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
            throw new \RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.returnCode') . $returnCode);
        }

        $stderrL = strtolower($stderr);

        if (strpos($stderrL, 'error') !== false || strpos($stderrL, 'unknown') !== false) {
            $this->logger->error($this->languageService->getLL('html2pdf.converter.generator.error.stderr') . '<pre>' . $stderr . '</pre>');
            throw new \RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.stderr'));
        }

        if (trim($stdout) === '') {
            $this->logger->error($this->languageService->getLL('html2pdf.converter.generator.error.stdout') . '<pre>' . $stderr . '</pre>');
            throw new \RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.stdout'));
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
}
