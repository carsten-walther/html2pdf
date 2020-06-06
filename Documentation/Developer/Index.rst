.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. _developer:

Developer Corner
================

Target group: **Developers**

In order to get a PDF version of a page, the corresponding page must be given the typeNum 8080 when called.

The best way to do this is to create a link that you insert on each page:

.. code-block:: typoscript

    lib.htmlToPdf = TEXT
    lib.htmlToPdf {
        value = Get this page as PDF.
        typolink {
            parameter.data = TSFE:id
            addQueryString = 1
            addQueryString.method = GET
            addQueryString.exclude = type
            additionalParams = &type=8080
            useCacheHash = 1
            returnLast = url
        }
    }

.. _configuration-typoscript:
