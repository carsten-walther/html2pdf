<?php

namespace CarstenWalther\Html2pdf\Hook;

use CarstenWalther\Html2pdf\Service\PdfService;
use DOMException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException as InvalidArgumentExceptionAlias;

class ContentPostProc
{
    /**
     * @param array $parameters
     * @param TypoScriptFrontendController $tsfe
     * @return void
     * @throws DOMException
     * @throws InvalidArgumentExceptionAlias
     */
    public function process(array $parameters, TypoScriptFrontendController $tsfe): void
    {
        $pObj = $parameters['pObj'];
        $pdfService = GeneralUtility::makeInstance(PdfService::class, $parameters['pObj']->config['config']);
        if (!$pObj->no_cache && $pdfService->isEnabled()) {
            $pObj->content = $pdfService->generatePdf($pObj->content);
        }
    }
}