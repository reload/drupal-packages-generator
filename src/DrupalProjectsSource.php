<?php


namespace kasperg\DrupalPackagesGenerator;


use Drupal\PackagistBundle\Parser\Project;
use Reload\Repack\Source\Source;
use RollingCurl\Request;
use RollingCurl\RollingCurl;

class DrupalProjectsSource extends Source
{

    const PROJECTS_URL = 'http://updates.drupal.org/release-history/project-list/all';
    const RELEASES_URL = 'http://updates.drupal.org/release-history/%s/%d.x';

    const ALL = 'all';

    protected $projects;
    protected $apiVersions;

    public function __construct($projects = null, $apiVersions = array(6, 7, 8))
    {
        if (empty($projects)) {
            $this->projects = self::ALL;
        }
        $this->apiVersions = $apiVersions;
    }

    /**
     * @inheritdoc
     */
    public function getPartition($entry) {
        return substr(trim($entry), 0, 1);
    }

    /**
     * @inheritdoc
     */
    public function getEntries()
    {
        $entries = array();

        if ($this->projects != self::ALL) {
            $entries = $this->projects;
        } else {
            $curl = new RollingCurl();
            $curl->get(self::PROJECTS_URL, null, array(CURLOPT_TIMEOUT => 0));
            $curl->setCallback(
                function (Request $request, RollingCurl $curl) use (&$entries) {
                    $projectsDocument = @simplexml_load_string($request->getResponseText());
                    if ($projectsDocument !== false) {
                        foreach ($projectsDocument->project as $project) {
                            if ($project->project_status == 'published') {
                                $entries[] = (string) $project->short_name;
                            }
                        }
                    }
                }
            );

            $curl->execute();

        }

        return $entries;
    }

    /**
     * @inheritdoc
     */
    public function getPackages($entries)
    {
        $packages = array();

        $curl = new RollingCurl();
        $curl->setSimultaneousLimit(200);
        $curl->setCallback(function(Request $request, Response $response, array $oprtions) use (&$packages)
        {
            try {
                $responseDocument = @simplexml_load_string($request->getResponseText());
                if ($responseDocument != false &&
                    $responseDocument->getName() != 'error') {
                    $project = new Project($responseDocument);
                    foreach (@$project->getComposerPackages() as $projectName => $projectPackages) {
                        $packages[] += $projectPackages;
                    }
                }
            } catch (\Exception $e) {
                // Do nothing for now.
            }
        });


        foreach ($entries as $project) {
            foreach ($this->apiVersions as $version) {
                $curl->get(sprintf(self::RELEASES_URL, $project, $version));
            }
        }

        $curl->execute();

        return $packages;
    }

}
