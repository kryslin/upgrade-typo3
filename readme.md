### Repeatable TYPO3 upgrade

The aim of this script is to make process of upgrade repeatable so you can easily repeat it on you local instance and 
run it also on staging and finally on production instance. 

## Install

Create subdirectory in your TYPO3 root (for example `.upgrade`) and inside this folder run:

`composer require sourcebroker/upgrade-typo3`

## Configuration

Basically the script search for branches with name "upgrade_*" switch to them and run commands (look for typo3-upgrade.php
 for more insights).
  
If you have some project specific SQL then put it in `.upgrade/config/project/[branch part].sql` (`.upgrade/config/project/76.sql`)
If you have some instance specific SQL then put it in `.upgrade/config/instances/[instance name]/[branch part].sql`

The `[branch part]` is taken from branch name. If branch name is "upgrade_76" then `[branch part]` is equal to `76`.
The `[instance name]` is taken from INSTANCE env var. Make sure its set in .env file of your project.
  
Run upgrade in your TYPO3 root directory  
`./.upgrade/vendor/bin/typo3-upgrade.php`
