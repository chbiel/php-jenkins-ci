<?php
namespace JenkinsCI\Object;

use JenkinsCI\AbstractObject;
use JenkinsCI\Jenkins;

/**
 * A Build is a currently building (or enqueued) Job
 */
class Build extends AbstractObject
{
    /**
     * @var string
     */
    const FAILURE = 'FAILURE';

    /**
     * @var string
     */
    const SUCCESS = 'SUCCESS';

    /**
     * @var string
     */
    const RUNNING = 'RUNNING';

    /**
     * @var string
     */
    const WAITING = 'WAITING';

    /**
     * @var string
     */
    const UNSTABLE = 'UNSTABLE';

    /**
     * @var string
     */
    const ABORTED = 'ABORTED';
    /**
     * @var
     */
    private $_jobName;
    /**
     * @var
     */
    private $_buildId;

    public function __construct($jobName, $buildId, Jenkins $jenkins = null)
    {
        $this->_jenkins = $jenkins;
        $this->_jobName = $jobName;
        $this->_buildId = $buildId;
    }

    public function getTimestamp() { return $this->get('timestamp') / 1000; }

    public function getDuration() { return $this->get('duration') / 1000; }

    public function getBuildNumber() { return $this->get('number'); }

    public function getProgress()
    {
        if (null !== ($executor = $this->getExecutor())) {
            return $executor->getProgress();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getBuiltOn() { return $this->get('builtOn'); }

    /**
     * @return bool
     */
    public function isRunning() { return Build::RUNNING === $this->getResult(); }

    /**
     * @return float|null
     */
    public function getEstimatedDuration()
    {
        //since version 1.461 estimatedDuration is displayed in jenkins's api
        //we can use it witch is more accurate than calcule ourselves
        //but older versions need to continue to work, so in case of estimated
        //duration is not found we fallback to calcule it.
        if ($estimatedDuration = $this->get('estimatedDuration')) {
            return $estimatedDuration;
        }

        $duration = null;
        $progress = $this->getProgress();
        if (null !== $progress && $progress >= 0) {
            $duration = ceil((time() - $this->getTimestamp()) / ($progress / 100));
        }

        return $duration;
    }

    /**
     * Returns remaining execution time (seconds)
     *
     * @return int|null
     */
    public function getRemainingExecutionTime()
    {
        $remaining = null;
        if (null !== ($estimatedDuration = $this->getEstimatedDuration())) {
            //be carefull because time from JK server could be different
            //of time from Jenkins server
            //but i didn't find a timestamp given by Jenkins api

            $remaining = $estimatedDuration - (time() - $this->getTimestamp());
        }

        return max(0, $remaining);
    }

    /**
     * @return Executor|null
     */
    public function getExecutor()
    {
        if (!$this->isRunning()) {
            return null;
        }

        foreach ($this->_jenkins->getExecutors() as $executor) {
            /** @var Executor $executor */
            if ($this->getUrl() === $executor->getBuildUrl()) {
                return $executor;
            }
        }

        return null;
    }


    /**
     * @return null|string
     */
    public function getResult()
    {
        $result = null;
        switch ($this->get('result')) {
            case 'FAILURE':
                $result = Build::FAILURE;
                break;
            case 'SUCCESS':
                $result = Build::SUCCESS;
                break;
            case 'UNSTABLE':
                $result = Build::UNSTABLE;
                break;
            case 'ABORTED':
                $result = Build::ABORTED;
                break;
            case 'WAITING':
                $result = Build::WAITING;
                break;
            default:
                // TODO log
                break;
        }

        return $result;
    }


    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf('job/%s/%d/api/json', $this->_jobName, $this->_buildId);
    }
}