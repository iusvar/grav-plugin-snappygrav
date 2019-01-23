# v1.9.1
## 01/23/2019

1. [](#bugfix)
    * Translated hardcoded strings. Thanks @hughbris [#43](https://github.com/iusvar/grav-plugin-snappygrav/issues/43)
    * Added control over the existence of wkhtmltopdf in the O.S. Thanks @hughbris [#44](https://github.com/iusvar/grav-plugin-snappygrav/issues/44)

# v1.9.0
## 01/10/2019

1. [](#new)
    * local language for the export button. Thanks @algofribaz [#41](https://github.com/iusvar/grav-plugin-snappygrav/issues/41)
    * Different title for single page or entire site. Credit as above
    * Added French language
    * Inform if the preferred library is missing (presence in composer.json is checked)
    * Inform the line of a possible error
    * Added fields on the collection of pages in administrator (no matter the theme adopted)
1. [](#bugfix)
    * Added a minimum autoload.php to avoid error. Thanks @dwitzig [#40](https://github.com/iusvar/grav-plugin-snappygrav/issues/40)
    * File fr.yaml in the right folder
    * Fixed error on the icon of the export button that turns into spinner


# v1.9.0-beta.1
## 11/15/2018

1. [](#new)
    * Added a text area to insert additional CSS.
    * Added option regarding inserting the first image of the page. The width and height can be changed.
    * Added option regarding the placement of the wkhtmltopdf library in the data folder. Useful for administrators because future plugin updates do not overwrite the binary file
    * Making snappygrav work with other plugins. Thanks @hughbris [#39](https://github.com/iusvar/grav-plugin-snappygrav/issues/39)
    * Added ability to use mPDF and TCPDF libraries.
    * The KnpLabs Snappy and Wkhtmltopdf libraries have been removed. Both these and mPDF and TCPDF must be installed by the administrator.

# v1.8.0
## 09/27/2018

1. [](#new)
    * [#37](https://github.com/iusvar/grav-plugin-snappygrav/issues/37) Add branch export for more subfolderlevels
    * [#36](https://github.com/iusvar/grav-plugin-snappygrav/issues/36) Add excluding subfolder from branch export
    * Updated h4cc/wkhtmltopdf-amd64 to 0.12.5
    * Updated h4cc/wkhtmltopdf-i386 to 0.12.4
    * Added settings for page_break.

# v1.7.0
## 09/04/2018

1. [](#new)
    * Added h4cc/wkhtmltopdf-amd64 library after suggestion - Make amd64 default [#33](https://github.com/iusvar/grav-plugin-snappygrav/issues/33)
    * Updated h4cc/wkhtmltopdf-i386 to 0.12.4
1. [](#bugfix)
    * Restored Markdown Info [#34](https://github.com/iusvar/grav-plugin-snappygrav/issues/34)

# v1.6.2
## 09/04/2018

1. [](#new)
    * Added settings for set_time_limit. 60 sec timeout [#25](https://github.com/iusvar/grav-plugin-snappygrav/issues/25)
1. [](#bugfix)
    * Search text not aligned with learn2 theme if snappygrav plugin is enabled [#32](https://github.com/iusvar/grav-plugin-snappygrav/issues/32)

# v1.6.1
## 06/13/2018

1. [](#bugfix)
    * Pages are not ordered [#30](https://github.com/iusvar/grav-plugin-snappygrav/issues/30)

# v1.6.0
## 12/21/2017

1. [](#new)
    * Added settings for Print Media Type [#26](https://github.com/iusvar/grav-plugin-snappygrav/issues/26)

# v1.5.5
## 09/04/2017

1. [](#bugfix)
    * Relative path of wkhtmltopdf folder incorrect [#23](https://github.com/iusvar/grav-plugin-snappygrav/issues/23)

# v1.5.4
## 09/04/2017

1. [](#bugfix)
    * Branch Selection [#22](https://github.com/iusvar/grav-plugin-snappygrav/issues/22)

# v1.5.3
## 09/04/2017

1. [](#improved)
    * New site with working [demo](http://iusvar.alwaysdata.net/grav/)
1. [](#bugfix)
    * Fixed undefined variable: where [#21](https://github.com/iusvar/grav-plugin-snappygrav/issues/21)

# v1.5.2
## 07/23/2017

1. [](#bugfix)
    * Correct dates in wrong format in CHANGELOG.md

# v1.5.1
## 07/21/2017

1. [](#bugfix)
    * Deleted unnecessary control [#20](https://github.com/iusvar/grav-plugin-snappygrav/issues/20)

# v1.5.0
## 07/20/2017

1. [](#new)
    * Added functionality for knowledge-base theme. See also [#10](https://github.com/iusvar/grav-plugin-snappygrav/issues/10)
    * Added the ability to print the current page [#17](https://github.com/iusvar/grav-plugin-snappygrav/issues/17) in top-down mode
    * Now the document is created on time without prior saving on the server
    * Added Nonce features
1. [](#improved)
    * Improved page collection selection
    * Delete unnecessary redefinitions of variables within some cycles
    * README.md cleaning
1. [](#bugfix)
    * Update copyright period of the LICENSE file
    * Update blueprints.yaml
    * Update languages.yaml
    * Fixed settings for wkhtmltopdf installed on the server [#19](https://github.com/iusvar/grav-plugin-snappygrav/issues/19)

# v1.4.2
## 05/23/2017

1. [](#bugfix)
    * Replaced break with exit
    * Improved the README
    * Uploading dependencies automatically delayed

# v1.4.1
## 05/05/2017

1. [](#bugfix)
    * Updated blueprints and languages

# v1.4.0
## 05/05/2017

1. [](#improved)
    * Added breadcrumbs [#16](https://github.com/iusvar/grav-plugin-snappygrav/issues/16)
1. [](#bugfix)
    * Check if wkhtmltopdf-i386 is executable
    * Better later than ever: removed the instance that builds the `snappy` object from the `foreach` cycle 


# v1.3.1-rc.1
## 04/02/2017

1. [](#improved)
    * Updated `README.md`
1. [](#bugfix)
    * Added `composer.json` for required libraries [#13](https://github.com/iusvar/grav-plugin-snappygrav/issues/13)
    * Added `.gitattributes`

# v1.3.0
## 03/30/2017

1. [](#new)
    * Added Česky translations [#11](https://github.com/iusvar/grav-plugin-snappygrav/pull/11) (Thanks to [@rbukovansky](https://github.com/rbukovansky) for the PR)
    * Added toggle buttons for preface option [#12](https://github.com/iusvar/grav-plugin-snappygrav/issues/12)
    
# v1.2.0
## 03/28/2017

1. [](#new)
    * Simplified connection with the creation of a function TWIG
    * Added admin translations
    * Added english and italian translations
1. [](#bugfix)
    * Bugfix plugin [#6](https://github.com/iusvar/grav-plugin-snappygrav/issues/6), [#7](https://github.com/iusvar/grav-plugin-snappygrav/issues/7) and [#10](https://github.com/iusvar/grav-plugin-snappygrav/issues/10)

# v1.1.1
## 10/30/2015

1. [](#bugfix)
    * Bugfix in blueprints.yaml [#5](https://github.com/iusvar/grav-plugin-snappygrav/pull/5)
    * Updated snappygrav.yaml
    * Added built_in_css in Settings Defaults

# v1.1.0
## 10/28/2015

1. [](#new)
    * Add the ability to a have custom template, with CSS [#3](https://github.com/iusvar/grav-plugin-snappygrav/pull/3) & [#4](https://github.com/iusvar/grav-plugin-snappygrav/pull/4)
    * Ability to print all website as a PDF (usefull for RTFM website) [#4](https://github.com/iusvar/grav-plugin-snappygrav/pull/4)
1. [](#improved)
    * Cleanup & delete debug... [#3](https://github.com/iusvar/grav-plugin-snappygrav/pull/3) & [#4](https://github.com/iusvar/grav-plugin-snappygrav/pull/4)
    * Updated `README.md` file with information to print all website as a PDF [#4](https://github.com/iusvar/grav-plugin-snappygrav/pull/4)
    * Modified the description of the plugin in `blueprints.yaml`
1. [](#bugfix)
    * Fixed the default zoom in `blueprints.yaml`

# v1.0.3
## 10/03/2015

1. [](#bugfix) 
    * Bugfix in blueprints.yaml

# v1.0.2
## 10/02/2015

1. [](#bugfix) 
    * Bugfix in CHANGELOG.md

# v1.0.1
## 10/01/2015

1. [](#improved)
    * Improved blueprints for Grav Admin plugin
    * Improved `README.md` file with more information
1. [](#bugfix) 
    * Bugfix in snappygrav.php
    * Bugfix in snappygrav.yaml

# v1.0.0
## 09/04/2015

1. [](#new)
    * Initial release.
