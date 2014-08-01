<?php
namespace Fusani\Auth\Adapter;

/**
 * This allows us to build several different types of adapters with
 * common methods.
 */
interface Adapter
{
    /**
     * Build an adapter by specifying a url where requests will be made.
     *
     * @param $url : the url where the requests will be made
     */
    public function __construct($url);

    /**
     * Method for making a get request
     *
     * @param $path : the path where the request will be made
     * @param $params : the parameters that will be sent
     */
    public function get($path, $params);

    /**
     * Method for making a post request.
     *
     * @param $path : the path where the request will be made
     * @param $params : the parameters that will be sent
     */
    public function post($path, $params);

    /**
     * Method for making a put request.
     *
     * @param $path : the path where the request will be made
     * @param $params : the parameters that will be sent
     */
    public function put($path, $params);
}
