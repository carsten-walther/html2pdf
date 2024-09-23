<?php

namespace CarstenWalther\Html2pdf\Report;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;
use CarstenWalther\Html2pdf\Configuration\Configuration;

class ExtensionStatus implements StatusProviderInterface
{
    protected array $reports = [];

    protected LanguageService $languageService;

    public function __construct(protected readonly LanguageServiceFactory $languageServiceFactory)
    {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    public function getStatus(): array
    {
        $this->reports = [];

        $passConfiguration = $this->checkConfiguration();
        $passProcOpen = $this->checkProcOpenFunctionExists();

        if ($passConfiguration && $passProcOpen) {
            $this->checkBinary();
        }

        $this->checkOperatingSystem();

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        if ($typo3Version->getMajorVersion() < 12) {
            $this->checkPostProcHook();
            $this->checkPageIndexingHook();
        }

        return $this->reports;
    }

    protected function checkConfiguration(): bool
    {
        $pass = false;

        try {
            Configuration::getBinaryPath();
            $pass = true;
        } catch (InvalidArgumentException $e) {
            // ...
        }

        $this->reports[] = GeneralUtility::makeInstance(Status::class,
            $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.configuration.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.configuration.description'),
            $pass ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR
        );

        return $pass;
    }

    protected function checkProcOpenFunctionExists(): bool
    {
        $pass = function_exists('proc_open');

        $this->reports[] = GeneralUtility::makeInstance(Status::class,
            $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.proc_open.title'),
            $pass ? 'OK' : 'Error',
            $pass ? '' : $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.proc_open.description'),
            $pass ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR
        );

        return $pass;
    }

    protected function checkBinary(): bool
    {
        $binary = Configuration::getBinaryPath();

        if (empty($binary)) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.binary.title'),
                'Error',
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.binary.description'),
                ContextualFeedbackSeverity::ERROR
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
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.generation.success.title'),
                $binary,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.generation.success.description'),
                ContextualFeedbackSeverity::OK
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.generation.error.title'),
                $stderr,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.generation.error.description') . $returnCode,
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $pass;
    }

    protected function checkOperatingSystem(): void
    {
        $os = PHP_OS;

        if (in_array(strtolower(PHP_OS), ['linux', 'unix'])) {
            if (function_exists('exec')) {
                $architecture = exec('uname -m');
                $os = $architecture ? sprintf('%s (%s)', PHP_OS, $architecture) : PHP_OS;
            }
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.operating_system.title'),
                $os,
                '',
                ContextualFeedbackSeverity::NOTICE
            );
        } elseif (in_array(strtolower($os), ['windows', 'darwin'])) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.operating_system.missing.' . strtolower(PHP_OS) . '.description'),
                ContextualFeedbackSeverity::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.operating_system.title'),
                $os,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.operating_system.missing.unknown.description'),
                ContextualFeedbackSeverity::INFO
            );
        }
    }

    protected function checkPostProcHook(): void
    {
        $hooking = implode('</li><li>',
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']);
        $hookCount = count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']);

        if ($hookCount > 1) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.contentPostProc.title'),
                sprintf('%d ' . $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.contentPostProc.used.by'),
                    $hookCount),
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.contentPostProc.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                ContextualFeedbackSeverity::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.contentPostProc.title'),
                'OK',
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.contentPostProc.description'),
                ContextualFeedbackSeverity::OK
            );
        }
    }

    protected function checkPageIndexingHook(): void
    {
        $hooking = implode('</li><li>',
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']);
        $hookCount = count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']);

        if ($hookCount > 1) {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.pageIndexing.title'),
                sprintf('%d ' . $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.pageIndexing.used.by'),
                    $hookCount),
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.pageIndexing.used.description') . '<br /><ol><li>' . $hooking . '</li></ol>',
                ContextualFeedbackSeverity::INFO
            );
        } else {
            $this->reports[] = GeneralUtility::makeInstance(Status::class,
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.pageIndexing.title'),
                'OK',
                $this->languageService->sL('LLL:EXT:html2pdf/Resources/Private/Language/locallang.xlf:html2pdf.report.check.pageIndexing.description'),
                ContextualFeedbackSeverity::OK
            );
        }
    }

    public function getLabel(): string
    {
        return 'HTML2PDF';
    }
}
