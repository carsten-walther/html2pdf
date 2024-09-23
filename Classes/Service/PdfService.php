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
    private ?array $pluginConfiguration;

    public function __construct(array $tsfeConfigArray)
    {
        $configuration = GeneralUtility::removeDotsFromTS($tsfeConfigArray);
        $this->pluginConfiguration = $configuration['tx_html2pdf'] ?? [];
    }

    public function isEnabled() : bool
    {
        return array_key_exists('enable', $this->pluginConfiguration) && $this->pluginConfiguration['enable'];
    }

    private function getBinaryOptions() : array
    {
        return array_key_exists('binOptions', $this->pluginConfiguration) ? $this->pluginConfiguration['binOptions'] : [];
    }

    /**
     * @throws DOMException
     * @throws InvalidArgumentException
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


