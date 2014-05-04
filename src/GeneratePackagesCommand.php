<?php


namespace kasperg\DrupalPackagesGenerator;


use Composer\Command\Command;
use Composer\Json\JsonFile;
use Drupal\PackagistBundle\Parser\Project;
use Exception;
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
        $packages = array();
        $includes = array();

        $projectsUrl = 'http://updates.drupal.org/release-history/project-list/all';
        $releasesUrl = 'http://updates.drupal.org/release-history/%s/%d.x';
        $apiVersions = array(6, 7, 8);
        $numProjects = 0;

        $curl = new RollingCurl();

        $progress = $this->getHelperSet()->get('progress');
        $progress->setBarWidth(80);

        if (sizeof($input->getArgument('projects')) > 0) {
            $projectBuckets = array('custom' => $input->getArgument('projects'));
        } else {
            $curl->get($projectsUrl);
            $curl->setCallback(
              function (\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) use (&$projectBuckets, &$numProjects) {
                  $projectsDocument = @simplexml_load_string($request->getResponseText());
                  if ($projectsDocument !== false) {
                      foreach ($projectsDocument->project as $project) {
                          if ($project->project_status == 'published') {
                              $bucket = substr(trim($project->short_name), 0, 1);
                              if (empty($projectBuckets[$bucket])) {
                                  $projectBuckets[$bucket] = array();
                              }
                              $projectBuckets[$bucket][] = (string) $project->short_name;

                              $numProjects++;
                          }
                      }
                  }
              }
            );
            $curl->execute();
        }

        $errors = array();

        $progress->setRedrawFrequency(ceil(($numProjects * sizeof($apiVersions)) / 1000));
        $progress->start($output, $numProjects * sizeof($apiVersions));

        foreach ($projectBuckets as $bucketName => $projectNames) {
            $packages = array();

            $curl = new RollingCurl();
            $curl->setSimultaneousLimit(200);

            $bucketWriter = new JsonFile($input->getArgument('output-dir') . '/packages-' . $bucketName . '.json');

            foreach ($apiVersions as $version) {
                foreach ($projectNames as $project) {
                    $curl->get(sprintf($releasesUrl, $project, $version));
                }
            }

            $curl->setCallback(
              function (\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) use (&$packages, $progress, &$errors) {
                  try {
                      $responseDocument = @simplexml_load_string($request->getResponseText());
                      if ($responseDocument != false &&
                          $responseDocument->getName() != 'error') {
                          @$project = new Project($responseDocument);
                          foreach (@$project->getComposerPackages() as $projectName => $projectPackages) {
                              if (empty($packages[$projectName])) {
                                  $packages[$projectName] = array();
                              }
                              $packages[$projectName] += $projectPackages;
                          }
                      }
                  } catch (Exception $e) {
                      $errors[$request->getUrl()] = $e->getMessage();
                  }

                  $progress->advance();
              }
            );
            $curl->execute();

            $bucketWriter->write(array('packages' => $packages));

            $includes['packages-' . $bucketName . '.json'] = array(
                'sha1' => sha1_file($bucketWriter->getPath()),
            );

        }

        $packagesWriter = new JsonFile($input->getArgument('output-dir') . '/packages.json');
        $packagesWriter->write(array('includes' => $includes));

        $progress->finish();

        if (!empty($errors)) {
            $output->writeln(sprintf('%d error(s) occurred:', sizeof($errors)));
            foreach ($errors as $url => $message) {
                $output->writeln(sprintf('%s: %s', $url, $message));
            }
        }
    }

}
