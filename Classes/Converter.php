<?php

namespace Walther\Html2Pdf;

/**
 * Class Converter
 * @package Walther\Html2Pdf
 */
class Converter
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * Report constructor.
     */
    public function __construct()
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->languageService = $this->objectManager->get(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
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
        $binary = \Walther\Html2Pdf\Config::getBinaryPath();

        if (empty($binary)) {
            throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException($this->languageService->getLL('html2pdf.converter.binary.error'));
        }

        $cmd = sprintf(
            '%s %s - -',
            escapeshellcmd($binary),
            $this->formatBinaryOptions($options)
        );

        if ($_GET['debug'] === 'TRUE') {
            die('<pre>' . print_r($cmd, TRUE) . '</pre>');
        }

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);

        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[2]);

        $returnCode = proc_close($proc);

        if ($returnCode > 1) {
            // usually thrown when an invalid binary is called
            throw new \RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.returnCode') . $returnCode);
        }

        $stderrL = strtolower($stderr);
        if (strpos($stderrL, 'error') !== false || strpos($stderrL, 'unknown') !== false) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
                $this->languageService->getLL('html2pdf.converter.generator.error.stderr') . '<pre>' . $stderr . '</pre>',
                \Walther\Html2Pdf\Config::EXTENSION_KEY,
                \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL
            );
            throw new \RuntimeException($this->languageService->getLL('html2pdf.converter.generator.error.stderr'));
        }

        if (trim($stdout) === '') {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
                $this->languageService->getLL('html2pdf.converter.generator.error.stdout') . '<pre>' . $stderr . '</pre>',
                \Walther\Html2Pdf\Config::EXTENSION_KEY,
                \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL
            );
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

        foreach ($options as $name => $value) {
            if ($value !== '') {
                $return[] = sprintf('--%s %s', escapeshellcmd($name), escapeshellarg($value));
            } else {
                $return[] = '--' . escapeshellcmd($name);
            }
        }

        return implode(' ', $return);
    }
}
