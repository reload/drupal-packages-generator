<?php

namespace Drupal\PackagistBundle\Parser;

use Drupal\PackagistBundle\Parser\Release;
use Composer\Package\Package;
use Composer\Package\Version\VersionParser;
use Composer\Package\Link;

class ComposerPackageConvert {

	protected $xml;
	protected $version_parser;

    protected $project_types = array(
      'project_module' => 'drupal-module',
      'project_theme' => 'drupal-theme',
      'project_distribution' => 'drupal-profile',
    );

	function __construct(Project $project) {
		$this->project = $project;
		$this->version_parser = new VersionParser();
		$this->requires = array(new Link(null, 'composer/installers', null, null, '*'));
	}

	public function ToComposerPackage(Release $release) {

		// reformat eg. 7.x-3.5 / 7.x-3.x-dev
		$version = $release->getMainVersion() . '.' . $release->getVersion();
		$version = str_replace(array('.x'), '', $version);

		// not breaking 5.1.4-2rc1 (views 5)
		try {
			$version_name = $this->version_parser->normalize($version);
		} catch (\Exception $e) {
			$version_name = $version;
		}

		$package = new Package('drupal/' . $this->project->xml->short_name, $version_name, $version);

		$package->setDistType('tar');
		$package->setDistUrl($release->getDownload());
		$package->setDistReference($release->getReference());

		$type = $this->project->getProjectType();
		if(!empty($type) && empty($this->project_types[$type])) {
			throw new \RuntimeException('Unknown project type of ' . $type);
		}

		$package->setType($this->project_types[$type]);
		$package->setRequires($this->requires);

		return $package;
	}


}
