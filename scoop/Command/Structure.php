<?php
namespace Scoop\Command;

class Structure extends \Scoop\Command
{
    public function execute($args)
    {
        $con = \Scoop\Context::connect();
        $con->exec('CREATE TABLE IF NOT EXISTS structs(
            name CHAR(21) PRIMARY KEY NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
         )');
        $sqoStruct = new \Scoop\Storage\SQO('structs', 's', $con);
        $creator = $sqoStruct->create(array('name'));
        $files = glob('app/structs/*.sql');
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
        if ($creator->hasData()) {
            $creator->run();
        }
    }
}
