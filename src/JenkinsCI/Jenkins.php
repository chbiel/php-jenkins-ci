<?php
namespace JenkinsCI;

use JenkinsCI\Object\Job;
use stdClass;

class Jenkins
{
    use JenkinsCrumbSupport;

    const API_JSON = 'api/json';
    const API_XML = 'api/xml';

    /**
     * @var string
     */
    private $_baseUrl;
    /**
     * @var string
     */
    private $_username;
    /**
     * @var string
     */
    private $_password;

    /**
     * @param string $baseUrl
     */
    public function __construct($baseUrl, $username = '', $password = '')
    {
        $this->_baseUrl = $baseUrl . ((substr($baseUrl, -1) === '/') ? '' : '/');
        $this->_username = $username;
        $this->_password = $password;
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
     * @param string $url
     * @param int $depth
     *
     * @return stdClass
     */
    public function get($url, $depth = 1)
    {
        $url = sprintf('%s' . $url . '?depth=' . $depth, $this->_baseUrl);
        $ret = $this->getUrl($url);

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
     * @todo
     */
    public function getCurrentlyBuildingJobs()
    {
        $url = sprintf("%s", $this->_baseUrl) . "/api/xml?tree=jobs[name,url,color]&xpath=/hudson/job[ends-with(color/text(),\%22_anime\%22)]&wrapper=jobs";
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
     * @param string $node
     * @return array
     * @throws \RuntimeException
     * @todo
     */
    public function getExecutors($node = '(master)')
    {
        $this->initialize();

        $executors = array();
        for ($i = 0; $i < $this->_jenkins->numExecutors; $i++) {
            $url = sprintf('%s/computer/%s/executors/%s/api/json', $this->_baseUrl, $node, $i);
            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $ret = curl_exec($curl);

            if (curl_errno($curl)) {
                throw new \RuntimeException(sprintf('Error during getting information for executors[%s@%s] on %s', $i, $node, $this->_baseUrl));
            }
            $infos = json_decode($ret);
            if (!$infos instanceof stdClass) {
                throw new \RuntimeException('Error during json_decode');
            }

            $executors[] = new Executor($infos, $node, $this);
        }

        return $executors;
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

    /**
     * @param string $url
     * @param string $body
     * @param array $curlOps Options for curl_setopt
     * @throws \RuntimeException
     */
    public function postUrl($url, $body, $curlOps = [])
    {
        $curl = curl_init($this->_baseUrl . $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        if ($this->_username && $this->_password) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        foreach ($curlOps as $key => $opt) {
            curl_setopt($curl, $key, $opt);
        }

        curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \RuntimeException(sprintf('Error during POSTing to "%s" (%s)', $url, curl_error($curl)));
        }
    }

    /**
     * @param string $url
     * @param array $curlOps Options for curl_setopt
     * @return mixed
     */
    public function getUrl($url, $curlOps = [])
    {
        $curl = curl_init($this->_baseUrl . $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->_username && $this->_password) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        foreach ($curlOps as $key => $opt) {
            curl_setopt($curl, $key, $opt);
        }

        $ret = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \RuntimeException(sprintf('Error during GETing from "%s" (%s)', $url, curl_error($curl)));
        }

        return $ret;
    }
}