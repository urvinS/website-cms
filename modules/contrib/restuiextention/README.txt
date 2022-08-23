# Rest UI Extention

This module provides extra functionality on top of Rest API. You should not
need this module to get an spec compliant JSON:API, this module is to
customize the output of Rest API.

This module adds the following features:

  - Removes extra php arrays from request and response.
  - Add error codes and description in response.
  - Replace and accepts taxanomy field name intead of target ID in request/response.
  
Installation
============
Once the module has been installed, all the above mentioned services will be invoked implicitly with REST UI.
To enable logging navigate to admin/config/restuiextention/default
(Configuration > Web Services > REST UI Extension Basic Configuration through the administration panel).