<?php

namespace CarstenWalther\Html2pdf\Middleware;

use CarstenWalther\Html2pdf\Service\PdfService;
use DOMException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

class Output implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws DOMException
     * @throws InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $pdfService = GeneralUtility::makeInstance(PdfService::class, $GLOBALS['TSFE']->config['config']);
        $response = $handler->handle($request);

        if (!$GLOBALS['TSFE']->no_cache) {
            return $response;
        }

        if (!$pdfService->isEnabled()) {
            return $response;
        }

        $body = $response->getBody();
        $body->rewind();

        return new HtmlResponse($pdfService->generatePdf($body->getContents()), 200, $response->getHeaders());
    }
}
