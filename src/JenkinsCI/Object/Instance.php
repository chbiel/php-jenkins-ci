<?php
namespace JenkinsCI\Object;


use JenkinsCI\Jenkins;

class Instance
{
    private $_baseUrl;

    /**
     * @param $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        $curl = curl_init($this->_baseUrl . Jenkins::API_JSON);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($curl);

        if (curl_errno($curl)) {
            return false;
        } else {
            // FIXME find a way to ensure availability
        }

        return true;
    }

}