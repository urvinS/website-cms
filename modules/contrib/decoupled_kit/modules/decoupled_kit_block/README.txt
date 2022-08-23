INTRODUCTION
------------

Decoupled Kit Block get JSON blocks data for current page. 
Need use ?path=[ALIAS]. Optionally may use ?mode=[link|data], 
default is the 'link'.

Block data has 'data' field which may contain block data or link to
the data depending of mode value. Block data may contain 'link'
field with JSON:API data if JSON:API module was enabled.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   Visit: https://www.drupal.org/node/1897420 for further information.

 * Enable module.


REQUIREMENTS
-------------

 * Enable the modules that are required for the module.


CONFIGURATION
-------------

* Go to [Your Site]/admin/config/decoupled_kit/block/config
  or: Administration > Configuration > System > Decoupled Kit > Decoupled Block
  and configure on form.

* Select default Data mode: link or data.


MAINTAINERS
-----------

Current maintainers:
 * Sergey Loginov (goodboy) - https://drupal.org/user/222910
