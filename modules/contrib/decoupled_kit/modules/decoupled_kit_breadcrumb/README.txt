INTRODUCTION
------------

Decoupled Kit Breadcrumb get JSON breadcrumbs data for current page.
Need use ?path=[ALIAS]. Breadcrumb data has three parts: frontpage,
main part and title. Main part uses Path patterns config.

You can also get breadcrumb data as block data using Decoupled Kit Block.


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

* Go to [Your Site]/admin/config/decoupled_kit/breadcrumb/config
  or: Administration > Configuration > System > Decoupled Kit
  > Decoupled Breadcrumb and configure on form.

* Select breadrumb options. Pattern: Type|Link|Title. Type is a
  alias part. For example: 'news|news.html|Latest news' 
  for 'news/somenews.html' page and news.html as news list page.


MAINTAINERS
-----------

Current maintainers:
 * Sergey Loginov (goodboy) - https://drupal.org/user/222910
