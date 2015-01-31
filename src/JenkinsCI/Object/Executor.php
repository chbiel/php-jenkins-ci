<?php
namespace JenkinsCI\Object;

use JenkinsCI\AbstractObject;

/**
 * An Executor represents one execution slot on a slave
 */
class Executor extends AbstractObject
{
    /**
     * @var string
     */
    private $_parentNode;

    /**
     * @var int
     */
    private $_idx;

    /**
     * @param string $_parentNode
     * @param int $_idx
     */
    function __construct($_parentNode, $_idx)
    {
        $this->_parentNode = $_parentNode;
        $this->_idx = $_idx;
    }

    public function getProgress() { return $this->get('progress'); }

    public function getNumber() { return $this->get('number'); }

    /**
     * @return int|null
     */
    public function getBuildNumber()
    {
        return ($this->get('currentExecutable')) ? $this->get('currentExecutable')->number : null;
    }

    /**
     * @return null|string
     */
    public function getBuildUrl()
    {
        return ($this->get('currentExecutable')) ? $this->get('currentExecutable')->url : null;
    }

    /**
     * @return void
     */
    public function stop()
    {
        $this->_jenkins->postUrl($this->_getExecutorBaseUrl() . 'stop', '');
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return sprintf($this->_getExecutorBaseUrl() . 'api/json');
    }

    protected function _getExecutorBaseUrl()
    {
        return sprintf('computer/%s/executors/%s/', $this->_parentNode, $this->_idx);
    }
}