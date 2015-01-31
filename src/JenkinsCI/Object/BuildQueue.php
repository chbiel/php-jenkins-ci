<?php
namespace JenkinsCI\Object;

use JenkinsCI\AbstractObject;

/**
 * The BuildQueue represents the queue of Build's that should be build in the (near) future
 */
class BuildQueue extends AbstractObject
{
    /**
     * @return array
     */
    public function getQueueItems()
    {
        $items = array();

        foreach ($this->get('items') as $item) {
            $items[] = new BuildQueueItem($item, $this->getJenkins());
        }

        return $items;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return 'queue/api/json';
    }
}