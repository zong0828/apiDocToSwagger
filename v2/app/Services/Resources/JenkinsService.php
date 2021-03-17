<?php
namespace App\Services\Resources;

use App\Services\Resources\Traits\HttpTrait;
use Exception;

/**
 * Jenkins Api
 *
 * @author Zong <zong.xie.udn@gmail.com>
 */
class JenkinsService
{
    use HttpTrait;

    private $host;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->host = env('JENKINS_HOST', 'http://127.0.0.1');
    }

    /**
     * 取得 jenkins 上的 apidoc json file
     *
     * @param string $path apidoc json path
     * @param string $host [option] jenkins_url
     *
     * @return array
     */
    public function getApiDoc(string $path, string $host = '')
    {
        try {
            $host = empty($host) ? $this->host : $host;

            return $this->get("{$host}{$path}");
        } catch (Exception $e) {
            // TODO
            throw new Exception("[Jenkins-ApiDoc]" . $e->getMessage());
        }
    }

    /**
     * getJenkinsHosts
     *
     * @return string
     */
    public function getJenkinsHosts()
    {
        return $this->host;
    }
}
