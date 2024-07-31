<?php

use Robo\Contract\TaskInterface;
use Robo\TaskInfo;

class RoboFile extends \Robo\Tasks
{
    private const PROJECT_NAME = 'wp-plugin-framework';
    private const BUILD_DIR = __DIR__.'/build';
    private const REPORT_DIR = __DIR__.'/report';
    private const VERSION_FILE = __DIR__.'/version.txt';
    private const COMPOSER_JSON = __DIR__.'/composer.json';

    private string $buildVersion = '9.9.9';

    private function getZipFileName(): string
    {
        return self::PROJECT_NAME .  '_' . $this->buildVersion . '.zip';
    }

    private function getZipFile(): string
    {
        return self::BUILD_DIR . '/' . $this->getZipFileName();
    }

    /**
     * Task to clean the project
     *
     * @return TaskInterface The task
     */
    public function clean(): TaskInterface
    {
        return $this->taskCleanDir([
            self::BUILD_DIR,
            self::REPORT_DIR
        ]);
    }

    /**
     * Task to setup the release.
     *
     * Includes bumping the version number and running the unit tests.
     *
     * @return TaskInterface The collection of tasks
     */
    public function release(): TaskInterface
    {
        $collection = $this->collectionBuilder();

        $collection
            ->addTask($this->prepare())
            ->addTask($this->bumpVersion())
            ->addTask($this->composerUpdate())
//            ->addTask($this->doLint())
            ->addTask($this->runPhpStan())
            ->addTask($this->test());

        return $collection;
    }

    /**
     * Task to setup necessary directories
     *
     * @return TaskInterface The task
     */
    protected function prepare(): TaskInterface
    {
        return $this->taskFilesystemStack()
            ->mkdir(self::BUILD_DIR)
            ->mkdir(self::REPORT_DIR);
    }

    /**
     * Task to bump the version number
     *
     * Updates version.txt and composer.json
     *
     * @return TaskInterface The collection of tasks
     */
    protected function bumpVersion(): TaskInterface
    {
        $oldVersion = file_get_contents(self::VERSION_FILE);

        $newVersion = $this->incrementVersion($oldVersion);

        $this->yell("build.version: $newVersion");

        $collection = $this->collectionBuilder();
        $collection
            ->taskReplaceInFile(self::VERSION_FILE)
            ->from($oldVersion)
            ->to($newVersion);
        $collection
            ->taskReplaceInFile(self::COMPOSER_JSON)
            ->regex('~"version" *: *"[0-9]+\.[0-9]+\.[0-9]+",~')
            ->to('"version" : "'.$newVersion.'",');

        return $collection;
    }

    /**
     * Task to run "composer update"
     *
     * @return TaskInterface The task
     */
    protected function composerUpdate(): TaskInterface
    {
        /**
         * We run "composer update" because setBuildVersion would have changed the
         * version number in composer.json so we need to update the hash in
         * composer.lock. Don't include "no-dev" as we need the dev dependancies
         * for unit tests. "composer install" will be run duing the release job to
         * remove the dev dependancies prior to packaging.
         */
        return $this->taskComposerUpdate();
    }

    protected function doLint()
    {
//         <phplint haltonfailure="true" cachefile="${reportDir}/phplint.cache">
//             <fileset dir="src">
//                 <include name="**/*.php"/>
//             </fileset>
//             <fileset dir="test">
//                 <include name="**/*.php"/>
//             </fileset>
//         </phplint>
    }

    /**
     * Task to run PHPStan
     *
     * @return TaskInterface The task
     */
    protected function runPhpStan(): TaskInterface
    {
        return $this->taskExec('vendor/bin/phpstan')
        ->arg('analyze')
        ->arg('--no-progress')
        ->arg('--error-format=github');
    }

    /**
     * Task to run the unit tests
     *
     * @return TaskInterface The task
     */
    public function test(): TaskInterface
    {
        return $this->taskPHPUnit();
    }

    /**
     * Task to package the library
     *
     * @param string $buildVersion The version number to label for the zip file
     *
     * @return TaskInterface The collection of tasks
     */
    public function package(string $buildVersion): TaskInterface
    {
        $this->buildVersion = $buildVersion;

        $collection = $this->collectionBuilder();
        $collection->addTask($this->prepare());
        $collection
            ->taskPack(self::getZipFile())
            ->addDir(self::PROJECT_NAME.'/src', 'src')
            ->addFile(self::PROJECT_NAME.'/composer.json', 'composer.json')
            ->addFile(self::PROJECT_NAME.'/composer.lock', 'composer.lock')
            ->exclude(['vendor\/.*'])
            ->run();

        return $collection;
    }

    /**
     * Advance to the next SemVer version.
     *
     * The behavior depends on the parameter $stage.
     *   - If $stage is empty, then the patch or minor version of $version is incremented
     *   - If $stage matches the current stage in the current version, then add one
     *     to the stage (e.g. alpha3 -> alpha4)
     *   - If $stage does not match the current stage in the current version, then
     *     reset to '1' (e.g. alpha4 -> beta1)
     *
     * @param string $version A SemVer version
     * @param string $stage dev, alpha, beta, rc or an empty string for stable.
     * @return string
     */
    protected function incrementVersion(string $version, string $stage = ''): string
    {
        $stable = empty($stage);

        preg_match('/-([a-zA-Z]*)(\d*)/', $version, $match);
        $match += ['', '', ''];
        $versionStage = $match[1];
        $versionStageNumber = $match[2];

        if ($versionStage != $stage) {
            $versionStageNumber = 0;
        }
        $version = preg_replace('/-.*/', '', $version);
        $versionParts = explode('.', $version);

        if ($stable) {
            $versionParts[count($versionParts)-1]++;
        }
        $version = implode('.', $versionParts);

        if (!$stable) {
            $version .= '-' . $stage;
            if ($stage != 'dev') {
                $versionStageNumber++;
                $version .= $versionStageNumber;
            }
        }
        return $version;
    }
}
