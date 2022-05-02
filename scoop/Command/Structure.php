<?php
namespace Scoop\Command;

class Structure extends \Scoop\Command
{
    public function execute($args)
    {
        $this->setArguments($args);
        $name = $this->getOption('name', 'default');
        $con = $this->getConnection($name);
        $this->createTable($con);
        $creator = $this->print($name, $con);
        if ($creator->hasData()) $creator->run();
        echo 'structure changed!';
    }

    private function createTable($con)
    {
        $con->exec('CREATE TABLE IF NOT EXISTS structs(
            name VARCHAR(255) PRIMARY KEY NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
         )');
    }

    private function getConnection($name)
    {
        $options = array();
        $user = $this->getOption('user');
        if ($user) $options['user'] = $user;
        $password = $this->getOption('password');
        if ($password) $options['password'] = $password;
        return \Scoop\Context::connect($name, $options);
    }

    function getFiles()
    {
        $path = 'app/structs/'.$this->getOption('schema', '');
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        return $this->glob($path.'*.sql');
    }

    private function glob($pattern)
    {
        $files = glob($pattern);
        $folders = glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT);
        foreach ($folders as $dir) {
            $files = array_merge($files, $this->glob($dir.'/'.basename($pattern)));
        }
        return $files;
    }

    private function print($name, $con)
    {
        $sqoStruct = new \Scoop\Storage\SQO($con->is('pgsql') ? 'public.structs' : 'struct', 's', $name);
        $creator = $sqoStruct->create(array('name'));
        $files = $this->getFiles();
        $structs = $sqoStruct->read('name')->run()->fetchAll(\PDO::FETCH_COLUMN, 0);
        $con->beginTransaction();
        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $structs)) {
                echo 'File '.$file.'... ';
                $content = file_get_contents($file);
                if ($content) {
                    $con->exec($content);
                    $creator->create(array($name));
                    echo "updated!\n";
                } else {
                    echo "pending!\n";
                }
            }
        }
        return $creator;
    }
}
