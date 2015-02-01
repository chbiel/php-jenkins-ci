<?php
namespace JenkinsCI\Object;

use JenkinsCI\AbstractObject;
use JenkinsCI\Jenkins;

/**
 * A Job is a Jenkins Job that can be build
 */
class Job extends AbstractObject
{
    const CONFIG_URL = 'config.xml';

    /**
     * @var string
     */
    protected $_jobName;

    /**
     * @param string $jobName
     * @param Jenkins $jenkins
     */
    function __construct($jobName, Jenkins $jenkins = null)
    {
        $this->_jobName = $jobName;
        $this->_jenkins = $jenkins;
    }

    /**
     * @param int $buildId
     * @return Build
     */
    public function getBuild($buildId)
    {
        return new Build($this->_jobName, $buildId, $this->_jenkins);
    }

    /**
     * @param array $params
     * @return bool
     */
    public function build($params = [])
    {
        $url = $this->_getJobBaseUrl();
        if ($params) {
            $url .= 'buildWithParameters';
        } else {
            $url .= 'build';
        }

        try {
            $this->_jenkins->postUrl($url, http_build_query($params));
        } catch (\RuntimeException $e) {
            // TODO log error message
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function buildAndWait($params = [])
    {
        $nextBuildNumber = $this->get('nextBuildNumber');

        $this->build($params);

        while ($this->refresh()->getLastBuild()->getBuildNumber() !== $nextBuildNumber) {
            sleep(1);
        }
        $runningBuild = $this->getLastBuild();
        $runningBuildNumber = $runningBuild->getBuildNumber();

        while (($runningBuild->isRunning() || $runningBuild->isPending()) && $runningBuild->getBuildNumber() === $runningBuildNumber) {
            sleep(1);
            $runningBuild->refresh();
        }

        return true;
    }

    /**
     * @return Build|null
     */
    public function getLastSuccessfulBuild()
    {
        if (null === ($lastSuccessfulBuild = $this->get('lastSuccessfulBuild'))) {
            return null;
        }
        return $this->getBuild($lastSuccessfulBuild->number);
    }

    /**
     * @return Build|null
     */
    public function getLastBuild()
    {
        if (null === ($lastBuild = $this->get('lastBuild'))) {
            return null;
        }
        return $this->getBuild($lastBuild->number);
    }

    /**
     * @return bool
     */
    public function isCurrentlyBuilding()
    {
        return $this->getLastBuild()->isRunning();
    }

    /**
     * @param bool $asString
     * @return string|\SimpleXMLElement
     */
    public function getConfig($asString = false)
    {
        $config = $this->_jenkins->getUrl($this->_getJobBaseUrl() . 'config.xml');
        return ($asString) ? $config : simplexml_load_string($config);
    }

    /**
     * @param string $config
     */
    public function updateConfig($config)
    {
        $this->_jenkins->postUrl($this->_getJobBaseUrl() . 'config.xml', $config, [CURLOPT_HEADER => ['Content-Type: text/xml']]);
    }

    public function delete()
    {
        $this->_jenkins->postUrl($this->_getJobBaseUrl() . 'doDelete', '');
    }

    /**
     * @param string $description
     */
    public function updateDescription($description)
    {
        $this->_jenkins->postUrl($this->_getJobBaseUrl() . 'description', $description);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_jenkins->getUrl($this->_getJobBaseUrl() . 'description');
    }

    public function pollScm()
    {
        $this->_jenkins->postUrl($this->_getJobBaseUrl() . 'polling', '');
    }

    protected function _getJobBaseUrl()
    {
        return sprintf('job/%s/', $this->_jobName);
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->_getJobBaseUrl() . 'api/json';
    }
}