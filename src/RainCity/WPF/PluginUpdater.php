<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * This class manages the options for the plugin
 *
 * @since      1.0.0
*/
class PluginUpdater
{
    private $log;

    private $pluginName;
    private $pluginSlug;
    private $entryPointFile;
    private $currentVersion;

    private $updateSearchDone = false;  // avoid looking for updates more than once per HTTP request
    private $updateEntry = null;        // cache the result of finding the update entry

    public function __construct($pluginName, $pluginSlug, $entryPointFile, $currentVersion) {
        $this->pluginName = $pluginName;
        $this->pluginSlug = $pluginSlug;
        $this->entryPointFile = $entryPointFile;
        $this->currentVersion = $currentVersion;

        $this->log = Logger::getLogger(get_class());

        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', array($this, 'checkUpdate'), 10, 2);
        // Define the alternative response for information checking
        add_filter('plugins_api', array($this, 'checkInfo'), 10, 3 );

        add_filter('auto_update_plugin', array($this, 'autoUpdatePlugin'), 10, 2);
    }


    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
     */
    public function checkUpdate($value, $transientName = '')        // NOSONAR
    {

        if (!empty( $value->checked ) ) {
            $entry = $this->getUpdateEntry();

            if(isset($entry)) {
                //     create filter for transitent injection
                $obj = new \stdClass();
                $obj->name = $this->pluginName;
                $obj->slug = $this->pluginSlug;
                $obj->plugin = $this->entryPointFile;
                $obj->new_version = $entry->version;
                $obj->package = $entry->url;

                $value->response[$this->entryPointFile] = $obj;
            }
        }

        return $value;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param boolean $false
     * @param array $action
     * @param object $arg
     * @return bool|object
     */
    public function checkInfo($obj, $action, $arg)
    {
        if (($action=='query_plugins' || $action=='plugin_information') &&
            isset($arg->slug) && $arg->slug === $this->pluginSlug)
        {
            $entry = $this->getUpdateEntry();

            if (isset($entry)) {
                $obj                = new \stdClass();
                $obj->slug          = $this->pluginSlug;
                $obj->plugin_name   = $this->pluginName;
                $obj->name          = $this->pluginName;
                $obj->new_version   = $entry->version;
                $obj->sections      = array(
                    'description'   => 'The latest version of ' . $this->pluginName
                );
                $obj->download_link = $entry->url;
            }
        }

        return $obj;
    }

    /**
     * Determine if plugin should be updated automatically
     *
     * @param boolean $update
     * @param object $item
     * @return boolean
     */
    public function autoUpdatePlugin($update, $item) {

        if ( $item->slug == $this->pluginSlug ) {
            return true; // Always update our plugin
        } else {
            return $update; // Else, use the normal API response to decide whether to update or not
        }
    }


    /**
     * Look for the most recent plugin update, removing any old entries as it
     * goes.
     *
     */
    private function getUpdateEntry () {
        // have we already looked this up once?
        if (false === $this->updateSearchDone) {
            /** @var ZipEntry */
            $newestEntry = null;

            $ipm = new IgnorePostsManager($this->pluginSlug);

            /** @var ZipEntry[] */
            $zipEntries = $this->findPluginUpdates($ipm->getPosts());

            if(!empty($zipEntries)) {
                foreach ($zipEntries as $entry) {
                    $this->inspectZipFile($newestEntry, $entry, $ipm);
                }
                $ipm->storePosts();
            }

            $this->updateSearchDone = true;
            $this->updateEntry = $newestEntry;
        }

        return $this->updateEntry;
    }

    private function inspectZipFile(ZipEntry &$newestEntry, ZipEntry $entry, IgnorePostsManager $ipm) {
        $entry->version = $this->getPluginVersion($entry->path);

        if (is_string($entry->version)) {
            $this->log->debug('getPluginVersion() returned: ' . $entry->version);

            // Is the entry found new that the installed version?
            if (version_compare($this->currentVersion, $entry->version) < 0) {
                if (isset($newestEntry)) {
                    // Is the entry newer that the 'newest' entry?
                    if (version_compare($newestEntry->version, $entry->version) < 0) {
                        $this->deleteUpdatePost ($newestEntry);
                        $newestEntry = $entry;
                    }
                    else {
                        $this->deleteUpdatePost ($entry);
                    }
                }
                else {
                    $newestEntry = $entry;
                }
            }
            else {
                $this->deleteUpdatePost ($entry);
            }
        }
        else {
            $this->log->info(
                'Found a file that looked like a plugin update but couldn\'t determing the verison number: ' .
                $entry->path
                );
            $ipm->addPost($entry->id);
        }
    }

    private function findPluginUpdates($ignorePosts) {
        $zipEntries = array();

        $posts = get_posts(array('post_type' => 'attachment',
                                 'post_mime_type' => 'application/zip',
                                 'posts_per_page' => -1,
                                 'exclude' => $ignorePosts));
        foreach ($posts as $post) {
            array_push($zipEntries, new ZipEntry($post->ID, $post->guid, get_attached_file($post->ID)));
        }

        return $zipEntries;
    }


    private function deleteUpdatePost ($entry) {
        $this->log->info('Removing old plugin update', array ('Version' => $entry->version, 'File' => $entry->path));
        wp_delete_post($entry->id, true);
    }


    private function getPluginVersion($zipFile) {
        $version = null;

        // Create a temporary folder to work in
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid(basename($zipFile, '.zip').'-');
        if (mkdir($tempDir)) {
            if (\WP_Filesystem(false, $tempDir) === true) {
                // Unzip to temp folder
                $result = unzip_file($zipFile, $tempDir);
                if (!is_wp_error( $result ) || $result ) {
                    $fqEntryPointFile = $tempDir . DIRECTORY_SEPARATOR . $this->entryPointFile;
                    if (file_exists($fqEntryPointFile)) {
                        $pluginData = get_plugin_data($fqEntryPointFile, false, false);

                        $version = $pluginData['Version'];
                    }
                }
                else {
                    $this->log->warning(
                        'Unable to extract entry point from {file}: {error}',
                        array('file' => $zipFile, 'error' => error_get_last()['message'])
                        );
                }
            }
            else {
                $this->log->critical(
                    'Unable to initialize WordPress file system: {err}',
                    array('err' => (error_get_last()['message']))
                    );
            }

            $this->deleteTempDir($tempDir);
        }
        else {
            $this->log->critical(
                'Unable to create folder for zip file contents: {dir} / {err}',
                array('dir' => $tempDir, 'err' => (error_get_last()['message']))
                );
        }

        error_clear_last();

        return $version;
    }

    private function deleteTempDir(string $tempDir): void {
        // Clean out the temporary folder and remove it
        $it = new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($tempDir);
    }
}

class ZipEntry
{
    public $id;
    public $url;
    public $path;
    public $version;

    public function __construct($id, $url, $path) {
        $this->id = $id;
        $this->url = $url;
        $this->path = $path;
    }
}

class IgnorePostsManager {
    const OPTION_NAME = 'raincity_wpf_ignore_posts_manager';

    private $pluginSlug;
    private $postArray = array();

    public function __construct($slug) {
        $this->pluginSlug = $slug;

        $options = get_option(self::OPTION_NAME, array());

        if (isset($options[$this->pluginSlug])) {
            $this->postArray = $options[$this->pluginSlug];
        }
    }

    public function getPosts() {
        return $this->postArray;
    }

    public function addPost($postId) {
        array_push($this->postArray, $postId);
    }

    public function storePosts() {
        $options = get_option(self::OPTION_NAME, array());

        $options[$this->pluginSlug] = $this->postArray;

        update_option(self::OPTION_NAME, $options);
    }
}
