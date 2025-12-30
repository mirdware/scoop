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
        $connection = $this->getConnection(
            $name,
            $command->getOption('user'),
            $command->getOption('password')
        );
        $this->createTable($connection);
        $this->update(
            $connection,
            $command->getOption('schema', ''),
            $command->getOption('tag', false)
        );
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

    private function createTable($con)
    {
        $con->exec('CREATE TABLE IF NOT EXISTS structs(
            name VARCHAR(255) PRIMARY KEY NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tag VARCHAR(255)
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

    private function collectFiles($filePatten, $collectedSqlFiles)
    {
        $rootSqlFiles = glob($filePatten . '/*.sql');
        if ($rootSqlFiles !== false) {
            $collectedSqlFiles = array_merge($collectedSqlFiles, $rootSqlFiles);
        }
        return $collectedSqlFiles;
    }

    private function getFiles($schema)
    {
        $baseStructPath = str_replace('\\', '/', realpath('app/structs'));
        $collectedSqlFiles = array();
        if (empty($schema)) {
            return $this->collectFiles($baseStructPath, $collectedSqlFiles);
        }
        if (preg_match('/^\{([^\}]+)\}$/', $schema, $match)) {
            $individualPatterns = explode(',', $match[1]);
            foreach ($individualPatterns as $pattern) {
                $collectedSqlFiles = array_merge($collectedSqlFiles, $this->getFiles(trim($pattern)));
            }
            return $collectedSqlFiles;
        }
        $regex = '#^' .
        preg_quote($baseStructPath, '#') . '/' .
        str_replace(array('*', '.'), array('[^/]*', '\.'), $schema) .
        '$#u';
        $directoryIterator = new \RecursiveDirectoryIterator($baseStructPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        $regexIterator = new \RegexIterator($recursiveIterator, $regex, \RegexIterator::MATCH);
        foreach ($regexIterator as $item) {
            if ($item->isDir()) {
                $collectedSqlFiles = $this->collectFiles($item->getPathname(), $collectedSqlFiles);
            }
        }
        return $collectedSqlFiles;
    }

    private function update($connection, $schema, $tag)
    {
        $tableName = $connection->is('pgsql') ? 'public.structs' : 'structs';
        $sqoStruct = new \Scoop\Persistence\SQO($tableName, 's', $connection);
        $creator = $sqoStruct->create(array('name'));
        $files = array_unique($this->getFiles($schema));
        $fileMap = $this->getFileMap($files);
        $files = array_keys($fileMap);
        $updater = $this->getUpdater($sqoStruct, $tag, $files);
        $files = array_diff($files, $sqoStruct->read('name')->run()->fetchAll(\PDO::FETCH_COLUMN, 0));
        $connection->beginTransaction();
        $lineWriter = $this->writer->withSeparator(' ');
        foreach ($files as $name) {
            $file = $fileMap[$name];
            $lineWriter->write("File <link:$file!> ...");
            $content = file_get_contents($file);
            if ($content) {
                $connection->exec($content);
                $creator->create(array($name));
                $this->writer->write('<success:updated!!>');
            } else {
                $this->writer->write('<warn:pending!!>');
            }
        }
        $this->save($creator, $updater);
        $connection->commit();
    }

    private function save($creator, $updater)
    {
        if ($creator->hasData()) {
            $creator->run();
            $this->writer->write('<done:Structure changed!!>');
        } else {
            $this->writer->write('<info:Nothing to do!!>');
        }
        if ($updater) {
            $updater->run();
        }
    }

    private function getFileMap($files)
    {
        $fileMap = array();
        foreach ($files as $filePath) {
            $fileMap[basename($filePath)] = $filePath;
        }
        return $fileMap;
    }

    private function getUpdater($sqoStruct, $tag, $files)
    {
        if ($tag && !empty($files)) {
            return $sqoStruct
            ->update(array('tag' => $tag))
            ->filter('name IN(:files)')
            ->bind(compact('files'));
        }
        return null;
    }
}
