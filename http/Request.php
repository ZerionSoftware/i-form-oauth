<?php namespace Iform\Http;

class Request {

    protected $curl = null;

    public function post($url, $params = array())
    {
        $this->init($url, function () use ($params) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($params));
        });

        return $this->execute();
    }

    public function get($url, $params = array())
    {
        $url = ! empty($params) ? $url . "?" . http_build_query($params) : $url;
        $this->init($url);

        return $this->execute();
    }

    public function put($url, $params = array())
    {
        $this->init($url, function ($params) use ($params) {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
        });

        return $this->execute();
    }

    public function delete($url, $params = array())
    {
        $this->init($url, function ($params) use ($params) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
        });

        return $this->execute();
    }

    protected function setCurl()
    {
        if (gettype($this->curl) !== 'resource') $this->curl = curl_init();
    }

    protected function init($url, Callable $setupFn = null)
    {
        $this->setCurl();
        curl_setopt($this->curl, CURLOPT_HEADER, 1);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        if ($setupFn) $setupFn();
    }

    protected function execute()
    {
        try {
            $this->authorize();

            $response = $this->handle();
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    protected function authorize()
    {
        return null;
    }

    protected function handle()
    {
        return null;
    }

    /**
     * close any hanging resource
     */
    function __destruct()
    {
        if (gettype($this->curl) == 'resource') curl_close($this->curl);
    }
}