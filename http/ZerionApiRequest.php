<?php namespace Iform\Http;

use Iform\Http\Request;

class ZerionApiRequest extends Request {

    /***
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $requestCount = 0;
        do {
            list ($response, $httpStatus) = $this->request();
            $isRequestTimedOut = $this->apiRateLimited($httpStatus);
            $requestCount ++;

            if ($isRequestTimedOut) sleep(1);
        } while ($isRequestTimedOut && $requestCount < 5);


        return $this->validateResponse($response, $httpStatus);
    }

    /**
     * @param $httpStatus
     *
     * @return bool
     */
    protected function apiRateLimited($httpStatus)
    {
        return $httpStatus < 100 || $httpStatus == 503 || $httpStatus == 504;
    }

    /**
     * @return array
     */
    public function request()
    {

        $response = array(
            $this->getResults(curl_exec($this->curl)),
            $this->getHttpStatus()
        );

        curl_close($this->curl);

        return $response;
    }

    /**
     * @param $response
     * @param $httpStatus
     *
     * @return mixed
     * @throws \Exception
     */
    protected function validateResponse($response, $httpStatus)
    {
        if ($httpStatus < 200 || $httpStatus >= 400) {
            throw new \Exception($response['body'], $httpStatus);
        }

        return json_encode($response);
    }

    /***
     * @return int|mixed
     */
    protected function getHttpStatus()
    {
        $errorCode = curl_errno($this->curl);
        $httpStatus = ($errorCode) ? $errorCode : curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        return $httpStatus;
    }

    /**
     * @param $response
     *
     * @return array
     */
    protected function getResults($response)
    {
        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

        return array(
            'header' => substr($response, 0, $header_size),
            'body' => substr($response, $header_size)
        );
    }
}