<?php
namespace JenkinsCI;

class AbstractObject
{
    /**
     * @var Jenkins
     */
    protected $_jenkins = null;

    /**
     * @var \stdClass
     */
    protected $_data;

    /**
     * @return $this
     */
    public function refresh()
    {
        $this->_data = $this->$this->_jenkins->get($this->getUrl());
        return $this;
    }

    /**
     * @return string
     */
    abstract protected function getUrl();

    /**
     * Calls refresh when data is empty
     *
     * @param string $propertyName
     *
     * @return string|int|null|\stdClass
     * @throws \RuntimeException
     */
    public function get($propertyName)
    {
        if (!$this->_data) $this->refresh();

        if ($this->_data instanceof \stdClass && isset($this->_data->$propertyName)) {
            return $this->_data->$propertyName;
        }

        throw new \RuntimeException(sprintf('You tried to get a property (%s) that is not available', $propertyName));
    }

    /**
     * @return Jenkins|null
     */
    public function getJenkins()
    {
        return $this->_jenkins;
    }

    /**
     * @param Jenkins $jenkins
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->_jenkins = $jenkins;
    }
}