<?php


namespace kasperg\DrupalPackagesGenerator;


use Reload\Repack\Source\Source;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\Output;

class ProgressSource extends Source
{

    /**
     * @var Source
     */
    protected $source;

    /**
     * @var array
     */
    private $entries;

    /**
     * @var ProgressBar
     */
    protected $progress;

    public function __construct(Source $source, Output $output)
    {
        $this->source = $source;
        $this->entries = $source->getEntries();
        $this->progress = new ProgressBar($output, sizeof($this->entries));
        $this->progress->setBarWidth(80);
        $this->progress->start();
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function getPartition($entry) {
        return $this->source->getPartition($entry);
    }

    public function getPackages($entries)
    {
        $packages = $this->source->getPackages($entries);
        $this->progress->advance(sizeof($entries));

        if ($this->progress->getStep() >= $this->progress->getMaxSteps()) {
            $this->progress->finish();
        }

        return $packages;
    }

} 
