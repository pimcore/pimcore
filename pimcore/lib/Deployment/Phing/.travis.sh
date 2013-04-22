#!/bin/bash
#-----------------------------------------------------------
#
# Purpose: Run phing in a travis environment
#
# Target system: travis-ci
#-----------------------------------------------------------

installPearTask ()
{
    echo -e "\nAuto-discover pear channels and upgrade ..."
    pear config-set auto_discover 1
    pear -qq channel-update pear.php.net
    pear -qq upgrade
    echo "... OK"

    echo -e "\nInstalling / upgrading phing ... "
    which phing >/dev/null                      &&
        pear upgrade pear.phing.info/phing ||
        pear install --alldeps pear.phing.info/phing
    # update paths
    phpenv rehash
    # re-test for phing:
    phing -v 2>&1 >/dev/null    &&
        echo "... OK"           ||
        return 1

    echo -e "\nInstalling / upgrading phpcpd ... "
    which phpcpd >/dev/null                      &&
        sudo pear upgrade pear.phpunit.de/phpcpd ||
        sudo pear install pear.phpunit.de/phpcpd
    phpenv rehash

    echo -e "\nInstalling / upgrading phploc ... "
    which phploc >/dev/null                      &&
        sudo pear upgrade pear.phpunit.de/phploc ||
        sudo pear install pear.phpunit.de/phploc
    phpenv rehash

    echo -e "\nInstalling / upgrading phpcs ... "
    which phpcs >/dev/null                             &&
        sudo pear upgrade pear.php.net/PHP_CodeSniffer ||
        sudo pear install pear.php.net/PHP_CodeSniffer
    phpenv rehash
    # re-test for phpcs:
    phpcs --version 2>&1 >/dev/null   &&
        echo "... OK"           ||
        return 1

    sudo apt-get install python-docutils
    pear install VersionControl_Git-alpha
    pear install VersionControl_SVN-alpha
    pear install pear/XML_Serializer-beta
    pear install --alldeps PEAR_PackageFileManager
    pear install --alldeps PEAR_PackageFileManager2
    pear install Net_Growl

    # update paths
    phpenv rehash
}


#-----------------------------------------------------------

    installPearTask &&
        echo -e "\nSUCCESS - PHP ENVIRONMENT READY." ||
        ( echo "=== FAILED."; exit 1 )

    if [[ $TRAVIS_PHP_VERSION < 5.3 ]]; then
    	pear install -f phpunit/File_Iterator-1.3.2
    	pear install -f phpunit/PHP_TokenStream-1.1.4
    	pear install -f phpunit/PHP_Timer-1.0.3
    	pear install -f phpunit/Text_Template-1.1.1
    else
        composer install
    fi

#    echo "=== BUILDING PHING ==="
#    cd build
#    phing -Dversion=2.0.0b1

    echo "=== TESTING PHING ==="
    cd test
    ../bin/phing


#------------------------------------------------------- eof
