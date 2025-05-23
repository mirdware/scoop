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
        $tag = $command->getOption('tag', false);
        $con = $this->getConnection($name, $command->getOption('user'), $command->getOption('password'));
        $this->createTable($con, $tag);
        $creator = $this->update($name, $command->getOption('schema', ''), $tag, $con);
        if ($creator->hasData()) {
            $creator->run();
            $this->writer->write('<done:Structure changed!!>');
        } else {
            $this->writer->write('<info:Nothing to do!!>');
        }
    }

    public function help()
    {
        $this->writer->write(
            'Update database with the struct files.',
            '',
            'Options:',
            '--schema => update only structs of a specific "schema"(folder)',
            '--name => use a diferent database connection than "default"',
            '--user => change the user of the database connection',
            '--password => change the password of the database connection',
            '--tag => creates a tag name for the executed structs'
        );
    }

    private function createTable($con, $tag)
    {
        $con->exec('CREATE TABLE IF NOT EXISTS structs(
            name VARCHAR(255) PRIMARY KEY NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
         )');
         if ($tag) {
            $con->exec('ALTER TABLE structs ADD COLUMN IF NOT EXISTS tag VARCHAR(255)');
         }
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

    private function update($connectionName, $schema, $tag, $con)
    {
        $sqoStruct = new \Scoop\Persistence\SQO($con->is('pgsql') ? 'public.structs' : 'structs', 's', $connectionName);
        $creator = $sqoStruct->create(array('name'));
        $files = $this->getFiles($schema);
        $structs = $sqoStruct->read('name')->run()->fetchAll(\PDO::FETCH_COLUMN, 0);
        $con->beginTransaction();
        foreach ($files as $index => $file) {
            $name = basename($file);
            if (!in_array($name, $structs)) {
                $this->writer->write(true, "File <link:$file!> ... ");
                $content = file_get_contents($file);
                if ($content) {
                    $con->exec($content);
                    $creator->create(array($name));
                    $this->writer->write('<success:updated!!>');
                } else {
                    $this->writer->write('<alert:pending!!>');
                }
            }
            $files[$index] = $name;
        }
        if ($tag) {
            $sqoStruct->update(array('tag' => $tag))
            ->filter('name IN(:files)')
            ->run(compact('files'));
        }
        return $creator;
    }
}
