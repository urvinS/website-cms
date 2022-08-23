#!/bin/bash
rm -rf /home/ec2-user/bhartiaxa-cms/
mkdir -p /home/ec2-user/bhartiaxa-cms/
cd /var/www/html/drupal/
git pull
chown nginx:nginx /var/www/html/drupal/ -R
