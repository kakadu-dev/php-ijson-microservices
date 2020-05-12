<?php
/**
 * Created by mikhail.
 * Date: 5/11/20
 * Time: 12:43
 */

namespace Kakadu\Microservices;

use console\microservice\exceptions\BaseException;

/**
 * Class    MjResponse
 *
 * @package Kakadu\Microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class MjResponse
{
    /**
     * @var string|integer|null
     */
    protected $id;

    /**
     * @var array|string
     */
    protected $result;

    /**
     * @var array|BaseException
     */
    protected $error;

    /**
     * MjResponse constructor.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->id     = $response['id'] ?? null;
        $this->result = $response['result'] ?? null;
        $this->error  = $response['error'] ?? null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $json = $this->toJSON();

        return $json ? json_encode($json, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * @return array|null
     */
    public function toJSON(): ?array
    {
        $result = [
            'jsonrpc' => '2.0',
        ];

        if ($this->id) {
            $result['id'] = $this->id;
        }

        if ($this->result !== null) {
            $result['result'] = $this->result;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error instanceof BaseException ? $this->error->toJSON() : $this->error;
        }

        // Notification response
        if (!isset($result['result']) && !isset($result['error'])) {
            return null;
        }

        return $result;
    }

    /**
     * Get response id
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id ?? 0;
    }

    /**
     * Get response result
     *
     * @return array|string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get response error
     *
     * @return array|BaseException
     */
    public function getError()
    {
        return $this->error;
    }
}
