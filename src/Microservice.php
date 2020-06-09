<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 14:28
 */

namespace Kakadu\Microservices;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use Kakadu\Microservices\exceptions\MicroserviceException;
use Kakadu\Microservices\interfaces\ILogDriver;

/**
 * Class    Microservice
 *
 * @package Kakadu\Microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class Microservice
{
    /**
     * @var null|Microservice
     */
    private static ?Microservice $_instance = null;

    /**
     * @var string|null microservice name
     */
    protected ?string $name = null;

    /**
     * @var array microservice options
     */
    protected array $options = [
        'version'        => '1.0.0',
        'env'            => 'development',
        'ijson'          => 'http://127.0.0.1:8001',
        'requestTimeout' => 1000 * 60 * 5, // 5 min
    ];

    /**
     * @var ILogDriver
     */
    protected ILogDriver $logDriver;

    /**
     * @var HttpClient
     */
    private HttpClient $_httpClient;

    /**
     * Microservice constructor.
     *
     * @param string          $name
     * @param array           $options
     * @param ILogDriver|bool $logDriver
     */
    protected function __construct(string $name, array $options = [], $logDriver = false)
    {
        $this->name    = $name;
        $this->options = array_merge($this->options, array_filter($options));

        if ($logDriver instanceof ILogDriver) {
            $this->logDriver = $logDriver;
        } else {
            $this->logDriver = new ConsoleLogDriver(!$logDriver);
        }

        $this->_httpClient = $this->createHttpClient();
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a microservice.");
    }

    /**
     * Get microservice instance
     *
     * @return Microservice
     */
    public static function getInstance(): Microservice
    {
        return self::$_instance;
    }

    /**
     * Create http client
     *
     * @return HttpClient
     */
    private function createHttpClient(): HttpClient
    {
        return new HttpClient([
            'base_uri' => $this->options['ijson'],
            'timeout'  => 0,
            'headers'  => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * Create microservice
     *
     * @param string             $name
     * @param array              $options
     * @param \Closure|null|bool $logDriver
     *
     * @return Microservice
     */
    public static function create(string $name, array $options = [], $logDriver = null): Microservice
    {
        if (self::$_instance !== null) {
            throw new \Exception('Microservice already created.');
        }

        self::$_instance = new self($name, $options, $logDriver);

        return self::$_instance;
    }

    /**
     * Send request to microservice
     *
     * @param string       $method
     * @param array|string $data
     * @param bool         $autoGenerateId
     * @param array        $requestConf
     *
     * @return MjResponse|null
     */
    public function sendServiceRequest(string $service, string $method, $data, bool $autoGenerateId = true, array $requestConf = []): ?MjResponse
    {
        $req = [
            'method' => $method,
            'params' => $data,
        ];

        if ($autoGenerateId) {
            $req['id'] = $this->getUuidv4();
        }

        $req['params']['payload']['sender'] = "$this->name (srv)";

        $request     = new MjRequest($req);
        $rawResponse = null;

        $logMethod = "$service.$method";

        try {
            $this->logDriver->log("    --> Request ($logMethod - {$request->getId()}): $request", 2, $request->getId());

            $resp        = $this->createHttpClient()->request('POST', "/$service", array_merge([
                'timeout' => $this->options['requestTimeout'],
                'body'    => $request,
            ], $requestConf));
            $rawResponse = $resp->getBody();
            $response    = json_decode($rawResponse->getContents(), true);

            if (!empty($response['error'])) {
                throw new MicroserviceException($response['error']['message'] ?? 'Unknown error.');
            }

            return new MjResponse($response ?? []);
        } catch (BadResponseException $exception) {
            $errMessage = $exception->getMessage();
            $errStatus  = 4;

            if ($exception->getResponse()->getStatusCode() === 404) {
                $errMessage = "Service '$service' is down.";
                $errStatus  = 5;
            }

            $excpt = new MicroserviceException($errMessage);
            $excpt->setStatus($errStatus);

            throw $excpt;
        } finally {
            $body    = $rawResponse ? : 'empty (async?)';
            $message = "    <-- Response ($logMethod - {$request->getId()}): $body";
            $this->logDriver->log($message, 3, $request->getId());
        }
    }

    /**
     * Get unique id
     *
     * @return string
     * @throws \Exception
     */
    private function getUuidv4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Start microservice
     *
     * @param \Closure $clb
     *
     * @return void
     */
    public function start(\Closure $clb): void
    {
        echo "\e[0m$this->name microservice started. Version: {$this->options['version']} ({$this->options['env']})\n";

        $request = $this->handleClientRequest();

        while (true) {
            $response = [];

            if ($requestId = $request['id'] ?? null) {
                $response['id'] = $requestId;
            }

            if ($request instanceof MicroserviceException) {
                $response['error'] = $request;
            } else {
                try {
                    $response['result'] = call_user_func($clb, $request['method'], $request['params'] ?? [], $request);
                } catch (\Exception $exception) {
                    $message = "Endpoint exception ({$request['method']}): {$exception->getMessage()}";

                    $excpt = new MicroserviceException($message);
                    $excpt->setStatus(1);

                    if ($this->isDev()) {
                        print_r("\e[31m" . $exception->getTraceAsString() . "\n");
                    }

                    $response['error'] = $excpt;
                }
            }

            $rsp = new MjResponse($response);

            $this->logDriver->log("<-- Response ({$rsp->getId()}): $rsp", 1, $rsp->getId());

            $request = $this->handleClientRequest($rsp, false);
        }
    }

    /**
     *
     * Handle client request (get/send)
     *
     * @param MjResponse|null $response previous task response
     * @param bool            $isFirstTask
     *
     * @return array|MicroserviceException
     */
    protected function handleClientRequest(?MjResponse $response = null, $isFirstTask = true)
    {
        $id      = 0;
        $from    = 'Client';
        $dataLog = '';

        try {
            $resp = $this->_httpClient->request('POST', $isFirstTask ? "/$this->name" : null, [
                'headers' => [
                    'type' => 'worker',
                ],
                'body'    => $response,
            ]);

            $body    = json_decode($resp->getBody()->getContents(), true);
            $logBody = json_encode($body, JSON_UNESCAPED_UNICODE);

            $id   = $body['id'] ?? 0;
            $from = $body['params']['payload']['sender'] ?? 'Client';

            $this->logDriver->log("--> Request ($id) from $from: $logBody", 0, $id);

            return $body;
        } catch (BadResponseException $exception) {
            if ($exception->getMessage() === 'socket hang up') {
                throw $exception;
            }

            $this->logDriver->log("--> Response ($id) from $from: $dataLog", 1, $id);

            $expt = new MicroserviceException($exception->getMessage());

            if ($isFirstTask) {
                throw $expt;
            }

            return $expt;
        }
    }

    /**
     * Is development environment
     *
     * @return bool
     */
    protected function isDev(): bool
    {
        $env = $this->options['env'];

        return substr('development', 0, strlen($env)) === $env;
    }
}
