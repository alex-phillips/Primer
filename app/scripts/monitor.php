<?php
/**
 * @author Alex Phillips
 * Date: 3/12/14
 * Time: 5:16 PM
 */

require_once(__DIR__ . '/../Config/config.php');

$config = array(
    'watch_path' => APP_ROOT . '/public/css/sass/sass/',
    'command_path' => APP_ROOT . '/public/css/sass/',
    'command' => 'compass compile',
);

$monitor = new files_monitor($config);
$monitor->run();

class files_monitor
{
    private $_watch_path;
    private $_command_path;
    private $_command;

    private $_files = array();
    private $_changes = false;

    public function __construct($config)
    {
        $this->_watch_path = $config['watch_path'];
        $this->_command_path = $config['command_path'];
        $this->_command = $config['command'];

        // Get files initial md5
        $path = realpath($this->_watch_path);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            /* @var $file SplFileInfo */
            $pathName = $file->getPath();
            $fileName = $file->getFilename();
            if ($fileName === '.' || $fileName === '..' || is_dir($pathName . '/' . $fileName)) {
                continue;
            }
            $this->_files[$pathName . '/' . $fileName] = md5_file($pathName . '/' . $fileName);
        }

        echo "\nStarting\n";
        echo "Monitoring $this->_watch_path to run $this->_command...\n";
    }

    public function run()
    {
        while (true) {
            $this->checkFiles();
            sleep(1);
        }
    }

    private function checkFiles()
    {
        $this->_changes = false;
        $xary = array();
        $path = realpath($this->_watch_path);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            /* @var $file SplFileInfo */
            $pathName = $file->getPath();
            $fileName = $file->getFilename();
            $fullPath = $pathName . '/' . $fileName;
            if ($fileName === '.' || $fileName === '..' || is_dir($fullPath)) {
                continue;
            }
            if (array_key_exists($fullPath, $this->_files)) {
                if ($this->_files[$fullPath] !== md5_file($fullPath)) {
                    $this->_changes = true;
                    $this->_files[$fullPath] = md5_file($fullPath);
                    echo "\n >> File $fileName has changed\n";
                }
                $xary[$fullPath] = $this->_files[$fullPath];
                unset($this->_files[$fullPath]);
            }
            else {
                $this->_changes = true;
                $xary[$fullPath] = md5_file($fullPath);
                echo "\n >> New file $fileName\n";
            }
        }
        foreach($this->_files as $deleted => $hash) {
            echo "\n >> Deleted file $deleted\n";
            $this->_changes = true;
        }

        if ($this->_changes == true) {
            echo "Running {$this->_command}\n";
            `cd {$this->_command_path}; {$this->_command};`;
            echo "Done.\n\n";
        }
        $this->_files = $xary;
    }
}