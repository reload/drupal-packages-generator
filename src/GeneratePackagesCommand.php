<?php


namespace kasperg\DrupalPackagesGenerator;


use Composer\Command\Command;
use Composer\Json\JsonFile;
use Drupal\PackagistBundle\Parser\Project;
use Exception;
use Reload\Repack\Packer\IncludesPacker;
use RollingCurl\RollingCurl;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePackagesCommand extends Command
{

    protected function configure()
    {
        $this->setName('drupal-packages-generator:generate-packages');
        $this->setDescription(
          'Generate packages.json from projects on Drupal.org'
        );
        $this->setDefinition(
          array(
            new InputArgument('output-dir', InputArgument::OPTIONAL, 'The directory to output the package file(s) to', getcwd()),
            new InputArgument('projects', InputArgument::IS_ARRAY, 'The projects to include in the package.json file. If no projects are specified then all projects are included', array()),
          )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = new DrupalProjectsSource($input->getArgument('projects'));
        $packer = new IncludesPacker(new ProgressSource($source, $output), $input->getArgument('output-dir'));
        $packer->generate();
    }

}
