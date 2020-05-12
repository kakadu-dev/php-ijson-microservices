<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 13:08
 */

namespace Kakadu\Microservices;

/**
 * Class    MjRequest
 *
 * @package Kakadu\Microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class MjRequest
{
    /**
     * @var string|integer|null
     */
    protected $id;

    /**
     * @var string
     */
    protected string $method;

    /**
     * @var array|null
     */
    protected ?array $params = [];

    /**
     * MjRequest constructor.
     *
     * @param array $request
     */
    public function __construct(array $request)
    {
        $this->id     = $request['id'] ?? null;
        $this->method = $request['method'] ?? '';
        $this->params = $request['params'] ?? null;
    }

    /**
     * @return string|null
     */
    public function __toString(): string
    {
        return json_encode($this->toJSON(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array
     */
    public function toJSON(): array
    {
        $result = [
            'jsonrpc' => '2.0',
        ];

        if ($this->id) {
            $result['id'] = $this->id;
        }

        $result['method'] = $this->method;

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        return $result;
    }

    /**
     * Get request id
     *
     * @return int|string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request params
     *
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }
}
