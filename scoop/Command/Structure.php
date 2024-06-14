<?php

namespace Scoop\Command;

class Structure extends \Scoop\Command
{
    protected function execute()
    {
        $name = $this->getOption('name', 'default');
        $con = $this->getConnection($name);
        $this->createTable($con);
        $creator = $this->update($name, $con);
        if ($creator->hasData()) {
            $creator->run();
            self::writeLine('Structure changed!', Color::BLUE);
        } else {
            self::writeLine('Nothing to do!', Color::RED);
        }
    }

    protected function help()
    {
        echo 'Update database with the struct files', PHP_EOL, PHP_EOL,
        'Options:', PHP_EOL,
        '--schema => update only structs of a specific "schema"(folder)', PHP_EOL,
        '--name => use a diferent database connection than "default"', PHP_EOL,
        '--user => change the user of the database connection', PHP_EOL,
        '--password => change the password of the database connection', PHP_EOL;
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
        if ($user) {
            $options['user'] = $user;
        }
        $password = $this->getOption('password');
        if ($password) {
            $options['password'] = $password;
        }
        return \Scoop\Context::connect($name, $options);
    }

    private function getFiles($schema)
    {
        if (preg_match('/^\{([^\}]*)\}$/', $schema, $match)) {
            $folders = explode(',', $match[1]);
            $files = array();
            foreach ($folders as $schema) {
                $files = array_merge($files, $this->getFiles(trim($schema)));
            }
            return array_unique($files);
        }
        $path = 'app/structs/' . $schema;
        if (strrpos($path, '/') !== strlen($path) - 1) {
            $path .= '/';
        }
        return $this->glob($path . '*.sql');
    }

    private function glob($pattern)
    {
        $files = glob($pattern);
        $folders = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($folders as $dir) {
            $files = array_merge($files, $this->glob($dir . '/' . basename($pattern)));
        }
        return $files;
    }

    private function update($name, $con)
    {
        $sqoStruct = new \Scoop\Persistence\SQO($con->is('pgsql') ? 'public.structs' : 'structs', 's', $name);
        $creator = $sqoStruct->create(array('name'));
        $files = $this->getFiles($this->getOption('schema', ''));
        $structs = $sqoStruct->read('name')->run()->fetchAll(\PDO::FETCH_COLUMN, 0);
        $con->beginTransaction();
        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $structs)) {
                echo 'File ', $file, '... ';
                $content = file_get_contents($file);
                if ($content) {
                    $con->exec($content);
                    $creator->create(array($name));
                    self::writeLine('updated!', Color::GREEN);
                } else {
                    self::writeLine('pending!', Color::YELLOW);
                }
            }
        }
        return $creator;
    }
}
