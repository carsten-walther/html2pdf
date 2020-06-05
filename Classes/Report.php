<?php

namespace Walther\Html2Pdf;

/**
 * Class Report
 * @package Walther\Html2Pdf
 */
class Report implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * @var array
     */
    protected $reports = [];

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
     * getStatus
     *
     * Returns status of filesystem
     *
     * @return array
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function getStatus()
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

            \Walther\Html2Pdf\Config::getBinaryPath();
            $pass = true;

        } catch (\TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException $e) {

            // ...
        }

        $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('html2pdf.report.check.configuration.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->getLL('html2pdf.report.check.configuration.description'),
            $pass ? \TYPO3\CMS\Reports\Status::OK : \TYPO3\CMS\Reports\Status::ERROR
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

        $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('html2pdf.report.check.proc_open.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->getLL('html2pdf.report.check.proc_open.description'),
            $pass ? \TYPO3\CMS\Reports\Status::OK : \TYPO3\CMS\Reports\Status::ERROR
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
        $binary = \Walther\Html2Pdf\Config::getBinaryPath();

        if (empty($binary)) {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.binary.title'),
                'Error',
                $this->languageService->getLL('html2pdf.report.check.binary.description'),
                \TYPO3\CMS\Reports\Status::ERROR
            );
            return false;
        }

        $cmd = escapeshellcmd($binary);

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fwrite($pipes[0], '');
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $returnCode = proc_close($proc);

        $pass = $returnCode <= 1;

        if ($pass) {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.generation.success.title'),
                $binary,
                $this->languageService->getLL('html2pdf.report.check.generation.success.description'),
                \TYPO3\CMS\Reports\Status::OK
            );
        } else {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.generation.error.title'),
                $stderr,
                $this->languageService->getLL('html2pdf.report.check.generation.error.description') . $returnCode,
                \TYPO3\CMS\Reports\Status::ERROR
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
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                '',
                \TYPO3\CMS\Reports\Status::NOTICE
            );
        } elseif (in_array(strtolower($os), ['windows', 'darwin'])) {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->getLL('html2pdf.report.check.operating_system.missing.' . strtolower(PHP_OS) . '.description'),
                \TYPO3\CMS\Reports\Status::INFO
            );
        } else {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->getLL('html2pdf.report.check.operating_system.missing.unknown.description'),
                \TYPO3\CMS\Reports\Status::INFO
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
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.title'),
                sprintf('%d ' . $this->languageService->getLL('html2pdf.report.check.contentPostProc.used.by'), $hookCount),
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                \TYPO3\CMS\Reports\Status::INFO
            );
        } else {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.title'),
                'OK',
                $this->languageService->getLL('html2pdf.report.check.contentPostProc.description'),
                \TYPO3\CMS\Reports\Status::OK
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
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.title'),
                sprintf('%d ' . $this->languageService->getLL('html2pdf.report.check.pageIndexing.used.by'), $hookCount),
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                \TYPO3\CMS\Reports\Status::INFO
            );
        } else {
            $this->reports[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.title'),
                'OK',
                $this->languageService->getLL('html2pdf.report.check.pageIndexing.description'),
                \TYPO3\CMS\Reports\Status::OK
            );
        }
    }
}
