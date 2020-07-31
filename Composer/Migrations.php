<?php

/*
 * Chill is a software for social workers
 * Copyright (C) 2014 Champs-Libres Coopérative <info@champs-libres.coop>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ComposerBundleMigration\Composer;

use Composer\Script\CommandEvent;
use Symfony\Component\Filesystem\Filesystem;
use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Installer\InstallationManager;
use Composer\Script\Event;

/**
 * Copy migrations files into expected dir
 * 
 * The script is called on composer event post-install-cmd or post-update-cmd
 *
 */
class Migrations
{
    
    /**
     * synchronize migrations files from the installed or updated bundle
     * to the root bundle
     * 
     * The destination migration dir may be configured in root package with 
     * `app-migrations-dir` key. Default to `app/DoctrineMigrations`
     * 
     * The migrations files are searched in __bundle_path__/Resources/migrations 
     * OR in the path defined by the 'migration-source' directory in the extra 
     * package information defined in package's composer.json file.
     * 
     * If the file is already present AND equal the script ask user for a confirmation 
     * to copy them.
     * 
     * 
     * @param CommandEvent $event
     * @throws \RuntimeException
     */
    public static function synchronizeMigrations(Event $event)
    {
        $packages = $event->getComposer()->getRepositoryManager()
              ->getLocalRepository()->getPackages();
        $installer = $event->getComposer()->getInstallationManager();
        $appMigrationDir = self::getDestinationDir($event->getComposer());
        $io = $event->getIO();
        
        $areFileMigrated = array();
        
        //migrate for root package
        $areFileMigrated[] = self::handlePackage('.', 
              $event->getComposer()->getPackage(),
              $io, 
              $appMigrationDir);
        
        foreach($packages as $package) {
           $areFileMigrated[] = self::handlePackage($installer->getInstallPath($package),
                 $package,
                 $io, 
                 $appMigrationDir);
        }
        
        if (in_array(true, $areFileMigrated)) {
            $io->write("<warning>Some migration files have been imported. "
                  . "You should run `php app/console doctrine:migrations:status` and/or "
                  . "`php app/console doctrine:migrations:migrate` to apply them to your DB.");
        }
    }
    
    private static function handlePackage(
          $packagePath,
          PackageInterface $package,
          IOInterface $io,
          $appMigrationDir
          )
    {
         //get path
            $installSuffixes = array_key_exists('migration-source-dir', $package->getExtra()) ? 
                  $package->getExtra()['migration-source-dir'] 
                  : ['Resources/migrations', 'migrations'];

            foreach ($installSuffixes as $installSuffix) {
                $migrationDir = $packagePath.'/'.$installSuffix;
                
                //check for files and copy them
                if (file_exists($migrationDir)) {
                    $foundFile = false; //memorize the fact that a file has been moved
                    
                    foreach (glob($migrationDir.'/Version*.php') as $fullPath) {
                        if ($io->isVeryVerbose()) {
                            $io->write("<info>Found a candidate migration file at $fullPath</info>");
                        }
                        
                        $result = static::checkAndMoveFile($fullPath, $appMigrationDir, $io);
                        // memorize if a file has been moved, but do not change if 
                        // a file has been moved during a previous loop
                        $foundFile = ($foundFile) ? $foundFile : $result; 
                    }
                    
                    return $foundFile;
                    
                } elseif (isset($package->getExtra()['migration-source-dir'])) {
                    throw new \RuntimeException("The source migration dir '$migrationDir'"
                          . " is not found");
                }
            }
    }
    
    /**
     * check if the file exists in dest dir, and if the file is equal. If not,
     * move the file do destination dir.
     * 
     * @param string $sourceMigrationFile
     * @param string $appMigrationDir
     * @param IOInterface $io
     * @return boolean
     */
    private static function checkAndMoveFile($sourceMigrationFile, $appMigrationDir, IOInterface $io)
    {
        //get the file name
        $explodedPath = explode('/', $sourceMigrationFile);
        $filename = array_pop($explodedPath);
        
        if (file_exists($appMigrationDir.'/'.$filename)) {
            if (md5_file($appMigrationDir.'/'.$filename) === md5_file($sourceMigrationFile)) {
                if ($io->isVeryVerbose()) {
                    $io->write("<info>found that $sourceMigrationFile is equal"
                          . " to $appMigrationDir/$filename</info>");
                }
                $doTheMove = false;
            } else {
                $doTheMove = $io->askConfirmation("<question>The file \n"
                      . " \t$sourceMigrationFile\n has the same name than the previous "
                      . "migrated file located at \n\t$appMigrationDir/$filename\n "
                      . "but the content is not equal.\n Overwrite the file ?[y,N]", false);
            }
        } else {
            $doTheMove = true;
        }
        
        //move the file
        if ($doTheMove) {
            $fs = new Filesystem();
            $fs->copy($sourceMigrationFile, $appMigrationDir.'/'.$filename);
            $io->write("<info>Importing '$filename' migration file</info>");
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the app migrations dir defined in root package, or 
     * 'app/DoctrineMigrations' instead.
     * 
     * @param Composer $composer
     * @return string
     */
    private static function getDestinationDir(Composer $composer)
    {
        $projectRootPath = dirname(\Composer\Factory::getComposerFile());
        $extras = $composer->getPackage()->getExtra();
        
        return (array_key_exists('app-migrations-dir',$extras)) ?
            $projectRootPath.'/'.$extras['app-migrations-dir'] :
            $projectRootPath.'/migrations';
    }
}
