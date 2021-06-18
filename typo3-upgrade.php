#!/usr/bin/env php
<?php

namespace SourceBroker\Typo3Upgrade;

// define root path (one dir up)
$typo3RootDir = realpath(getcwd()) . '/';
define('T3U_TYPO3_DIR', $typo3RootDir);

$upgradeDir = realpath( __DIR__ . '/../../..');
define('T3U_UPGRADE_DIR', $upgradeDir);

require_once(__DIR__ . '/../../autoload.php');

// cleanup opcache
clearCache();

// get composer and run initial install
if (!file_exists('composer.phar')) {
    $composer = getopt(null, ["composer:"]);
    $version = '';
    if (!empty($composer['composer'])) {
        $version = ' --version=' . $composer['composer'];
    }
    copy('https://getcomposer.org/installer', 'composer-setup.php');
    exec('php composer-setup.php' . $version);
    unlink('composer-setup.php');
}

$instance = getCurrentInstance();
$upgradeBranches = getUpgradeBranches();
$isFirst = true;

foreach ($upgradeBranches as $numericVersion => $branch) {
    $numericVersion = (int)$numericVersion;
    run('echo "---------------------- git checkout ' . $branch . '"');
    run('git reset --hard');
    run('git checkout ' . $branch);
    run('rm -rf typo3_src typo3 index.php typo3conf/ext typo3temp vendor typo3conf/realurl_autoconf.php');
    run('git reset --hard');

    clearCache();
    run(PHP_BINARY . ' composer.phar install');
    clearCache();

    run(PHP_BINARY . ' vendor/bin/typo3cms database:updateschema "*.add,*.change"');
    doRun(T3U_UPGRADE_DIR . '/run/project/' . $numericVersion . '.txt');
    doRun(T3U_UPGRADE_DIR . '/run/instances/' . $instance . '/' . $numericVersion . '.txt');
    if (!$isFirst) {
        if ($numericVersion > 95) {
            run(PHP_BINARY  . ' vendor/bin/typo3cms upgrade:prepare');
            run(PHP_BINARY . ' vendor/bin/typo3cms upgrade:run all --no-interaction');
        } else {
            run(PHP_BINARY . ' vendor/bin/typo3cms upgrade:all');
        }
    }

    dbUpdate(__DIR__ . '/sql/' . $numericVersion . '.sql');
    dbUpdate(T3U_UPGRADE_DIR . '/config/project/'. $numericVersion . '.sql');
    dbUpdate(T3U_UPGRADE_DIR . '/config/instances/' . $instance . '/' . $numericVersion . '.sql');

    $isFirst = false;
}
