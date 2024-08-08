<?php

namespace Scoop\Command\Handler;

class Structure
{
    private $writer;

    public function __construct(\Scoop\Command\Writer $writer)
    {
        $this->writer = $writer;
    }
    public function execute($command)
    {
        $name = $command->getOption('name', 'default');
        $con = $this->getConnection($name, $command->getOption('user'), $command->getOption('password'));
        $this->createTable($con);
        $creator = $this->update($name, $command->getOption('schema', ''), $con);
        if ($creator->hasData()) {
            $creator->run();
            $this->writer->writeLine('Structure changed!', \Scoop\Command\Style\Color::BLUE);
        } else {
            $this->writer->writeLine('Nothing to do!', \Scoop\Command\Style\Color::RED);
        }
    }

    public function help()
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

    private function getConnection($name, $user, $password)
    {
        $options = array();
        if ($user) {
            $options['user'] = $user;
        }
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

    private function update($name, $schema, $con)
    {
        $sqoStruct = new \Scoop\Persistence\SQO($con->is('pgsql') ? 'public.structs' : 'structs', 's', $name);
        $creator = $sqoStruct->create(array('name'));
        $files = $this->getFiles($schema);
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
                    $this->writer->writeLine('updated!', \Scoop\Command\Style\Color::GREEN);
                } else {
                    $this->writer->writeLine('pending!', \Scoop\Command\Style\Color::YELLOW);
                }
            }
        }
        return $creator;
    }
}
