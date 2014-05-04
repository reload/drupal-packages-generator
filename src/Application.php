<?php


namespace kasperg\DrupalPackagesGenerator;

use \Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication {

    public function __construct()
    {
        parent::__construct();

        $this->add(new GeneratePackagesCommand());
    }

} 
