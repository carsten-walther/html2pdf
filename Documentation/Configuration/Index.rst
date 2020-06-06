.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. _configuration:

Configuration
=============

Target group: **Developers, Integrators**

To use this extension wkhtmltopdf must be installed on the server. Should this not be the case,
the supplied software can be used. To do this, you must set the appropriate version in the extension settings  select from wkhtmltopdf.

Available for selection:
* wkhtmltopdf-0.12.4_linux-generic-i386
* wkhtmltopdf-0.12.4_linux-generic-amd64
* custom

If you choose 'custom', don't forget to set the path to the local binary file of wkhtmltopdf under 'binPathCustom'.

Typical Configuration
---------------------

Example of TypoScript Configuration:

.. code-block:: typoscript

    # cat=basic/enable/10; type=options[wkhtmltopdf-0.12.4_linux-generic-i386,wkhtmltopdf-0.12.4_linux-generic-amd64,custom]; label=Select a wkhtmltopdf binary to use.
    binPath = custom

    # cat=basic/enable/20; type=string; label=Custom wkhtmltopdf binary. Don't forget to enable it with the option above.
    binPathCustom = /usr/local/bin/wkhtmltopdf


.. _configuration-typoscript:
