#!/bin/env bash
####################################################
#
# This is executed after installing the package
# via RPM or Debian package management software
#
####################################################

echo "Restarting apache"
service httpd graceful

echo "Restarting Coupon Worker"
supervisorctl
restart couponworker

