<?php

namespace Walther\Html2pdf\Report;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;
use Walther\Html2pdf\Configuration\Configuration;

/**
 * Class ExtensionStatus
 *
 * @package Walther\Html2pdf\Report
 */
class ExtensionStatus implements StatusProviderInterface
{
    /**
     * @var array
     */
    protected $reports = [];

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * ExtensionStatus constructor.
     *
     * @param \TYPO3\CMS\Core\Localization\LanguageService $languageService
     */
    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
        $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
    }

    /**
     * getStatus
     *
     * Returns status of filesystem
     *
     * @return array
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function getStatus() : array
    {
        $this->reports = [];

        $passConfiguration = $this->checkConfiguration();
        $passProcOpen = $this->checkProcOpenFunctionExists();

        if ($passConfiguration && $passProcOpen) {
            $this->checkBinary();
        }

        $this->checkOperatingSystem();
        $this->checkPostProcHook();
        $this->checkPageIndexingHook();

        return $this->reports;
    }

    /**
     * checkConfiguration
     *
     * check if the configuration of the extension is ok
     *
     * @return bool
     */
    protected function checkConfiguration() : bool
    {
        $pass = false;

        try {
            Configuration::getBinaryPath();
            $pass = true;
        } catch (InvalidArgumentException $e) {
            // ...
        }

        $this->reports[] = GeneralUtility::makeInstance(Status::class,
            $this->languageService->getLL('html2pdf.report.check.configuration.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->getLL('html2pdf.report.check.configuration.description'),
            $pass ? Status::OK : Status::ERROR
        );

        return $pass;
    }

    /**
     * checkProcOpenFunctionExists
     *
     * check that proc_open() is not disallowed
     *
     * @return bool
     */
    protected function checkProcOpenFunctionExists() : bool
    {
        $pass = function_exists('proc_open');

        $this->reports[] = GeneralUtility::makeInstance(Status::class,
            $this->languageService->getLL('html2pdf.report.check.proc_open.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->getLL('html2pdf.report.check.proc_open.description'),
            $pass ? Status::OK : Status::ERROR
        );

        return $pass;
    }

    /**
     * checkBinary
     *
     * check if the binary can be run
     *
     * @return bool
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    protected function checkBinary() : bool
    {
        $binary = Configuration::getBinaryPath();

        if (empty($binary)) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.binary.title'),
                'Error',
                $this->languageService->getLL('html2pdf.report.check.binary.description'),
                Status::ERROR
            );
            return false;
        }

        $cmd = escapeshellcmd($binary);

        $specs = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $pipes = [];

        $process = proc_open($cmd, $specs, $pipes);

        $stderr = '';
        $returnCode = 0;

        if (is_resource($process)) {

            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // 2 => readable handle connected to child stderr

            fwrite($pipes[0], '');
            fclose($pipes[0]);

            stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $returnCode = proc_close($process);
        }

        $pass = $returnCode <= 1;

        if ($pass) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.generation.success.title'),
                $binary,
                $this->languageService->getLL('html2pdf.report.check.generation.success.description'),
                Status::OK
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.generation.error.title'),
                $stderr,
                $this->languageService->getLL('html2pdf.report.check.generation.error.description') . $returnCode,
                Status::ERROR
            );
        }

        return $pass;
    }

    /**
     * checkOperatingSystem
     *
     * check the used operating system and give some hint on the binary to use
     */
    protected function checkOperatingSystem() : void
    {
        $os = PHP_OS;

        if (in_array(strtolower(PHP_OS), ['linux', 'unix'])) {
            if (function_exists('exec')) {
                $architecture = exec('uname -m');
                $os = $architecture ? sprintf('%s (%s)', PHP_OS, $architecture) : PHP_OS;
            }
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                '',
                Status::NOTICE
            );
        } elseif (in_array(strtolower($os), ['windows', 'darwin'])) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->getLL('html2pdf.report.check.operating_system.missing.' . strtolower(PHP_OS) . '.description'),
                Status::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->getLL('html2pdf.report.check.operating_system.missing.unknown.description'),
                Status::INFO
            );
        }
    }

    /**
     * checkPostProcHook
     *
     * check for conflicts with with other extensions using 'contentPostProc-output' of 'tslib/class.tslib_fe.php'
     */
    protected function checkPostProcHook() : void
    {
        $hooking = implode('</li><li>', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']);
        $hookCount = count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']);

        if ($hookCount > 1) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.title'),
                sprintf('%d ' . $this->languageService->getLL('html2pdf.report.check.contentPostProc.used.by'), $hookCount),
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                Status::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.title'),
                'OK',
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.description'),
                Status::OK
            );
        }
    }

    /**
     * checkPageIndexingHook
     *
     * check for conflicts with with other extensions using 'pageIndexing' of 'tslib/class.tslib_fe.php'
     */
    protected function checkPageIndexingHook() : void
    {
        $hooking = implode('</li><li>', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']);
        $hookCount = count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']);

        if ($hookCount > 1) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.title'),
                sprintf('%d ' . $this->languageService->getLL('html2pdf.report.check.pageIndexing.used.by'), $hookCount),
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                Status::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.title'),
                'OK',
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.description'),
                Status::OK
            );
        }
    }
}
