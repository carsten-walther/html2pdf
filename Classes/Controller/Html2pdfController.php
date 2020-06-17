<?php

namespace Walther\Html2pdf\Controller;

use RuntimeException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Walther\Html2pdf\Converter\Converter;

/**
 * Class Html2pdfController
 *
 * @package Walther\Html2pdf\Controller
 */
class Html2pdfController
{
    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $pObj;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * Html2pdfController constructor.
     */
    public function __construct(LanguageService $languageService = NULL)
    {
        $this->languageService = $languageService;
        if (!$this->languageService) {
            $this->languageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageService::class);
        }
        $this->languageService->includeLLFile('EXT:html2pdf/Resources/Private/Language/locallang.xlf');
    }

    /**
     * hookOutput
     *
     * called before any page content is send to the browser
     *
     * @param $params
     * @param $pObj
     *
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function hookOutput(&$params, $pObj) : void
    {
        $this->pObj = $pObj;

        if (!$this->pObj->no_cache) {
            // if: page is cached -> page should already be processed
            return;
        }

        if (!$this->isEnabled()) {
            // if: post-processing is not enabled for this page type
            return;
        }

        $this->processHook();
    }

    /**
     * isEnabled
     *
     * returns true if this page should be converted to PDF
     * if explicitly disabled it throws an exception for TYPO3 4.6 and up or
     * calls tslib_fe::pageNotFoundAndExit() for TYPO3 4.5 and below
     *
     * @return bool
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     */
    protected function isEnabled() : ?bool
    {
        if (!array_key_exists('tx_html2pdf.', $this->pObj->config['config']) || (!array_key_exists('enable', $this->pObj->config['config']['tx_html2pdf.']) && !array_key_exists('enable.', $this->pObj->config['config']['tx_html2pdf.']))) {
            //if: tx_czwkhtmltopdf was not configured for this page type
            return false;
        }

        if ($GLOBALS['TSFE']->cObj->stdWrap($this->pObj->config['config']['tx_html2pdf.']['enable'], $this->pObj->config['config']['tx_html2pdf.']['enable.'])) {
            //if: tx_html2pdf was configured and is enabled
            return true;
        }

        //if: tx_html2pdf was explicitly disabled
        $this->throw404($this->languageService->getLL('html2pdf.controller.throw404'));

        return false;
    }

    /**
     * throw404
     *
     * abort page rendering and show a 404 page
     *
     * @param $message
     *
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     */
    protected function throw404($message) : void
    {
        if (class_exists(PageNotFoundException::class)) {
            throw new PageNotFoundException($message);
        }
    }

    /**
     * processHook
     *
     * process a hook
     *
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    protected function processHook() : void
    {
        $converter = GeneralUtility::makeInstance(Converter::class);

        if (!$converter) {
            throw new RuntimeException($this->languageService->getLL('html2pdf.controller.converter.init.error'));
        }

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $objectManager->get(UriBuilder::class);
        $url = $uriBuilder
            ->reset()
            ->setTargetPageUid($this->pObj->id)
            ->setTargetPageType(8081)
            ->setCreateAbsoluteUri(true)
            ->buildFrontendUri();

        $content = file_get_contents($url);

        $this->pObj->content = $converter->convert($content, $this->getBinaryOptions());
    }

    /**
     * get the stdWrapped options
     *
     * @return array
     */
    protected function getBinaryOptions() : array
    {
        if (!array_key_exists('tx_html2pdf.', $this->pObj->config['config']) || !array_key_exists('binOptions.', $this->pObj->config['config']['tx_html2pdf.']) || !is_array($this->pObj->config['config']['tx_html2pdf.']['binOptions.'])) {
            // if: configuration is invalid
            return [];
        }

        return $this->pObj->config['config']['tx_html2pdf.']['binOptions.'];
    }

    /**
     * hook_indexContent
     *
     * called before anything is written to the cache
     *
     * @param $pObj
     *
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException|\TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function hook_indexContent($pObj) : void
    {
        $this->pObj = $pObj;

        if ($this->pObj->no_cache) {
            // if: page is not cached -> page will be processed before output
            return;
        }

        if (!$this->isEnabled()) {
            // if: post-processing is not enabled for this page type
            return;
        }

        $this->processHook();
    }
}
