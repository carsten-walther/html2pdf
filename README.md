Html2PDF for TYPO3
=============================

[![Issues](https://img.shields.io/github/issues/carsten-walther/html2pdf)](https://img.shields.io/github/issues/carsten-walther/html2pdf)
[![Forks](https://img.shields.io/github/forks/carsten-walther/html2pdf)](https://github.com/carsten-walther/html2pdf/network/members)
[![Stars](https://img.shields.io/github/stars/carsten-walther/html2pdf)](https://github.com/carsten-walther/html2pdf/stargazers)
[![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/carsten-walther/html2pdf)](https://github.com/carsten-walther/html2pdf/releases/latest)
[![License](https://img.shields.io/github/license/carsten-walther/html2pdf)](LICENSE.txt)
[![GitHub All Releases](https://img.shields.io/github/downloads/carsten-walther/html2pdf/total)](https://github.com/carsten-walther/html2pdf/releases/latest)

A wrapper to let TYPO3 generate PDF files from html pages. Uses wkhtmltopdf, a binary that is using the print functionality of the webkit render engine to create PDFs.

About the extension
-------------------
This extension will give you the possibility to print pages as pdf.

How to install?
---------------

Just call ```composer req carsten-walther/html2pdf``` or install the extension via the extension manager.

How to use it?
--------------

html2pdf provides a special page type with typeNum 8080. Generate a link and attach this page type to the GET parameters.

Configuration
-------------

Install wkhtmltopdf in your server environment or use custom versions for your needs. Select the version in extension configuration settings in the Install-Tool.

Sponsoring
----------
Do you like this extension and do you use it on production environments? Please help me to maintain this extension and
become a sponsor.
