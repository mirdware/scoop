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
    }

    private function createTable($con)
    {
        $con->exec('CREATE TABLE IF NOT EXISTS structs(
            name CHAR(21) PRIMARY KEY NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
         )');
    }

    private function getConnection($name)
    {
        $options = array();
        $user = $this->getOption('database');
        if ($user) $options['user'] = $user;
        $password = $this->getOption('database');
        if ($password) $options['password'] = $password;
        return \Scoop\Context::connect($name, $options);
    }

    private function print($name, $con)
    {
        $sqoStruct = new \Scoop\Storage\SQO('structs', 's', $name);
        $creator = $sqoStruct->create(array('name'));
        $files = glob('app/structs/*.sql');
        $con->beginTransaction();
        foreach ($files as $file) {
            $name = substr($file, strrpos($file, '/') + 1);
            $count = $sqoStruct->read('COUNT(*)')
            ->filter('name = :name')
            ->run(compact('name'))
            ->fetchColumn();
            if (!$count) {
                echo 'File '.$file.'... ';
                $content = file_get_contents($file);
                $con->exec($content);
                $creator->create(array($name));
                echo "updated\n";
            }
        }
        return $creator;
    }
}
