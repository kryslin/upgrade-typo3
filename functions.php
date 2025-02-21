<?php

namespace SourceBroker\Typo3Upgrade;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

function clearCache()
{
    clearstatcache(true);
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

function run($command, $return = false)
{
    $commandWithDirChange = 'cd '. escapeshellarg(T3U_TYPO3_DIR) .' && '. $command;

    $process1 = new Process('echo "------------------>>> ' . $command . '"');
    $process1->setTty(true);
    $process1->setTimeout(86400);
    $process1->run();

    $process = new Process($commandWithDirChange);
    if (!$return) {
        $process->setTty(true);
    }
    $process->setTimeout(86400);
    $process->run();

    if ($return) {
        return $process->getOutput();
    }
}

function getCurrentInstance()
{
    $dotenv = new Dotenv(true);
    $dotenv->load(T3U_TYPO3_DIR . '.env');

    return getenv('INSTANCE');
}

function getUpgradeBranches()
{
    $branchesRaw = run('git ls-remote 2>/dev/null | grep -ohE \'upgrade_([0-9]*)\'', true);
    $branchesRawEntries = explode("\n", $branchesRaw);

    $branches = [];
    foreach ($branchesRawEntries as $branchRaw) {
        $branchRaw = trim($branchRaw);
        if (!$branchRaw) {
            continue;
        }

        $version = str_replace('upgrade_','', $branchRaw);
        $version = str_pad($version, 3, '0', STR_PAD_LEFT);
        $branches[$version] = $branchRaw;
    }


    ksort($branches, SORT_ASC | SORT_NUMERIC);

    return $branches;
}

function getDbConfiguration()
{
    $configurationJson = run(PHP_BINARY . ' vendor/bin/typo3cms configuration:showactive DB --json', true);
    // cleanup depreciations and any other stuff before json
    $configurationJson = substr($configurationJson, strpos($configurationJson,'{'), strlen($configurationJson));
    $configuration = json_decode($configurationJson, true);

    if (isset($configuration['Connections']['Default'])) {
        return [
            'database' => $configuration['Connections']['Default']['dbname'],
            'host' => $configuration['Connections']['Default']['host'],
            'port' => $configuration['Connections']['Default']['port'],
            'username' => $configuration['Connections']['Default']['user'] ,
            'password' => $configuration['Connections']['Default']['password'],
        ];
    } else {
        return [
            'database' => $configuration['database'],
            'host' => $configuration['host'],
            'port' => $configuration['port'],
            'username' => $configuration['username'],
            'password' =>  $configuration['password'],
        ];
    }
}

function dbUpdate($filePath)
{
    if (!file_exists($filePath)) {
        return;
    }

    $filePathAbs = realpath($filePath);

    $configuration = getDbConfiguration();
    run('mysql -u ' . $configuration['username'] . ' -p' . $configuration['password'] . ' ' . $configuration['database'] . ' < ' . $filePathAbs);
}

function doRun($filePath)
{
    if (!file_exists($filePath)) {
        return;
    }

    $filePathAbs = realpath($filePath);
    foreach (explode("\n", file_get_contents($filePathAbs)) as $command) {
        if ($command) {
            run($command);
        }
    }
}
