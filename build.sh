#!/bin/bash

if [[ -z "$PHP_HOME" ]] ; then
	echo "PHP_HOME" environment variable should be set to the directory where PHP is installed
elif ! [[ -e $PHP_HOME/php ]] ; then
	echo "PHP_HOME" is set but does not appear to refer to a PHP installation. Php executable was not found.
else
	source ./resetVersionFiles.sh;

	PHING_PHAR="${BASH_SOURCE%/*}/bin/phing*.phar"
	$PHP_HOME/php $PHING_PHAR $@
fi

echo "Build completed at $(date)"
