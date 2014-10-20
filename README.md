sphinxsearch
============

This magento extension provides integration for Magento CE with a sphinx search server.

Versions
--------
sphinxsearch was tested on Magento CE version 1.7.X.X and 1.8.0.0.
It probably works with 1.6.X.X too, but i haven't tested it yet.
Earlier versions of Magento won't work without changes in the extension code. 

Installation
------------
* Use modman (https://github.com/colinmollenhour/modman) for easy installation of this extension.
Integration with magento connect will be provided in the near future.
* If you don't want to use modman you can install the extension manually instead:
  * Just copy the following directory with all contained files and subdirs to your shop root directory. Use this exact path, create subdirs if necessary!
    * `app/code/community/Gfe/SphinxSearch`
  * Also copy the following 2 files to your shop directory:
    * `app/etc/modules/Gfe_SphinxSearch.xml`
    * `lib/sphinxapi.php`
* Logout from magento backend to execute extension setup files.
* Login to backend to configure sphinxsearch under Configuration/Catalog/Sphinx Search Engine.
* Recreate index catalog_search

Installing sphinx server
------------------------
Please refer to the sphinx installation guide for your server OS.

Just a quick guide for Debian GNU/Linux or Ubuntu:

* `# apt-get install sphinxsearch`
* Then copy sphinx.conf.example to /etc/sphinxsearch/sphinx.conf and edit according to your database configuration.
This config file provides sphinx search server all information to create an its index for all products in your magento shop.
* Edit /etc/default/sphinxsearch (change NO to YES)
* re-create sphinx' index: `# indexer --all`
* start sphinx search with `# /etc/init.d/sphinxsearch start`

Done!

Acknowledgements
----------------
I first integrated sphinx search into Magento 1.4.X.X with the help of this post:  
http://tonyhb.com/using-sphinx-within-magento-plus-optimising-search-result-rankings-weights-and-relevancy  
You can find this integration by user tonyhb here on github:  
https://gist.github.com/2727341  
I turned it into a full extension since then.

