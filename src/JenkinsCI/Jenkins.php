<?php
namespace JenkinsCI;

use JenkinsCI\Object\Job;
use stdClass;

class Jenkins
{
    use JenkinsCrumbSupport;

    const API_JSON = '/api/json';
    const API_XML = '/api/json';

    /**
     * @var string
     */
    private $_baseUrl;

    /**
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->_baseUrl = $baseUrl . ((substr($baseUrl, -1) === '/') ? '' : '/');
    }

    /**
     * @param string $jobName
     *
     * @return Job
     */
    public function getJob($jobName)
    {
        return new Job($jobName, $this);
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        $data = $this->get('api/json');

        $jobs = array();
        foreach ($data->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;
    }

    /**
     * @return Queue
     * @throws \RuntimeException
     */
    public function getQueue()
    {
        $data = $this->get('queue/api/json');

        return new Queue($data, $this);
    }

    /**
     * @param string $url
     * @param int    $depth
     *
     * @return stdClass
     */
    public function get($url, $depth = 1)
    {
        $url = sprintf('%s' . $url . '?depth=' . $depth, $this->_baseUrl);
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $response_info = curl_getinfo($curl);

        if (200 != $response_info['http_code']) {
            throw new \RuntimeException(sprintf('Error during getting information from url %s', $url));
        }

        if (curl_errno($curl)) {
            throw new \RuntimeException(sprintf('Error during getting information from url %s', $url));
        }
        $data = json_decode($ret);
        if (!$data instanceof stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }

        return $data;
    }


    /**
     * Get the currently building jobs
     * @return array
     * @throws \RuntimeException
     */
    public function getCurrentlyBuildingJobs()
    {
        $url = sprintf("%s", $this->_baseUrl)
            . "/api/xml?tree=jobs[name,url,color]&xpath=/hudson/job[ends-with(color/text(),\%22_anime\%22)]&wrapper=jobs";
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($curl);

        $errorMessage = sprintf('Error during getting all currently building jobs on %s', $this->_baseUrl);

        if (curl_errno($curl)) {
            throw new \RuntimeException($errorMessage);
        }
        $xml = simplexml_load_string($ret);
        var_dump($xml);
        $builds = $xml->xpath('/jobs');
        $buildingJobs = [];
        foreach ($builds as $build) {
            $buildingJobs[] = new Job($build->job->name, $this);
        }

        return $buildingJobs;
    }



    /**
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }
}