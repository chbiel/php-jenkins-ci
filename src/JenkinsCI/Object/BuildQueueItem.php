<?php
namespace JenkinsCI\Object;

use JenkinsCI\AbstractObject;
use JenkinsCI\Jenkins;

/**
 * A BuildQueueItem represents a single Build within the BuildQueue
 */
class BuildQueueItem extends AbstractObject
{
    /**
     * @param \stdClass $itemData
     * @param Jenkins $jenkins
     */
    function __construct($itemData, Jenkins $jenkins = null)
    {
        $this->_data = $itemData;
        $this->_jenkins = $jenkins;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return '';
    }

    /**
     * No refresh allowed
     */
    public function refresh() { }
}