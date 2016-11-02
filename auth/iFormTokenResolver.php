<?php namespace Iform\Auth;

use Iform\Http\ZerionApiRequest;
use Iform\Auth\Token\Jwt;


/**
 * @category Authentication
 * @package  iForm\Authentication
 * @author   Seth Salinas <ssalinas@zerionsoftware.com>
 * @license  http://opensource.org/licenses/MIT
 */
class iFormTokenResolver {

    /**
     * Credentials - secret.  See instructions for acquiring credentials
     *
     * @var string
     */
    private $secret;
    /**
     * Credentials - client key.  See instructions for acquiring credentials
     *
     * @var string
     */
    private $client;
    /**
     * oAuth - https://ServerName.iformbuilder.com/exzact/api/oauth/token
     *
     * @var string
     */
    private $url;
    /**
     * Jwt class
     *
     * @var Jwt|null
     */
    private $jwt = null;
    /**
     * iForm instance
     *
     * @var RequestHandler |null
     */
    private $request = null;

    /**
     * @param array $config
     * @param null  $requester Dependency
     * @param null  $jwt       Dependency
     *
     * @throws \Exception
     */
    function __construct(array $config, $requester = null, $jwt = null)
    {
        $this->client = $config['client'];
        $this->secret = $config['secret'];
        $this->url = trim($config['url']);

        $this->request = $requester ?: new ZerionApiRequest();
        $this->jwt = $jwt ?: new Jwt();
    }

    /**
     * @return mixed
     */
    private function getAssertion()
    {
        $iat = time();
        $exp = 600;
        $payload = array(
            "iss" => $this->client,
            "aud" => $this->url,
            "exp" => $iat + $exp,
            "iat" => $iat
        );

        return $this->jwt->encode($payload, $this->secret);
    }

    /**
     * API OAuth url
     *
     * @param string $url
     *
     * @return boolean
     */
    private function isZerionOauth($url)
    {
        return strpos($url, "exzact/api/oauth/token") !== false;
    }

    /**
     * @throws \Exception
     */
    private function validateEndpoint()
    {
        if (empty($this->url) || ! $this->isZerionOauth($this->url)) {
            throw new \Exception('Invalid url: Valid format https://SERVER_NAME.iformbuilder.com/exzact/api/oauth/token');
        }
    }

    /**
     * Build query parameter string
     *
     * @return string
     */
    private function getTokenParams()
    {
        return array(
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion"  => $this->getAssertion()
        );
    }

    /**
     * Request/get token
     *
     * @return string
     */
    public function getToken()
    {
        try {
            $this->validateEndpoint();

            $result = $this->extractAccessToken(
                $this->request->post($this->url, $this->getTokenParams())
            );
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $results
     *
     * @return string token || error msg
     */
    private function extractAccessToken($results)
    {
        $token = json_decode($results, true);

        return isset($token['access_token']) ? $token['access_token'] : $token['error'];
    }
}

