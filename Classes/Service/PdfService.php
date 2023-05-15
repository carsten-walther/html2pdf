<?php

namespace CarstenWalther\Html2pdf\Service;

use CarstenWalther\Html2pdf\Converter\Converter;
use DOMException;
use RuntimeException;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

class PdfService
{
    /**
     * @var array|null
     */
    private ?array $pluginConfiguration;

    /**
     * @param array $tsfeConfigArray
     */
    public function __construct(array $tsfeConfigArray)
    {
        $configuration = GeneralUtility::removeDotsFromTS($tsfeConfigArray);
        $this->pluginConfiguration = $configuration['tx_html2pdf'] ?? [];
    }

    /**
     * @return bool
     */
    public function isEnabled() : bool
    {
        return array_key_exists('enable', $this->pluginConfiguration) && $this->pluginConfiguration['enable'];
    }

    /**
     * @return array
     */
    private function getBinaryOptions() : array
    {
        return array_key_exists('binOptions', $this->pluginConfiguration) ? $this->pluginConfiguration['binOptions'] : [];
    }

    /**
     * @param string $content
     *
     * @return string
     * @throws InvalidArgumentException|DOMException
     */
    public function generatePdf(string $content) : string
    {
        $converter = GeneralUtility::makeInstance(Converter::class, GeneralUtility::makeInstance(LanguageServiceFactory::class));
        if (!$converter) {
            throw new RuntimeException('Converter could not be initialized.');
        }
        return $converter->convert($content, $this->getBinaryOptions());
    }
}


