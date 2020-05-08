<?php

namespace DocDoc\RgsApiClient;

use DocDoc\RgsApiClient\Dto\RgsApiParamsInterface;
use DocDoc\RgsApiClient\Exception\BadRequestRgsException;
use DocDoc\RgsApiClient\Exception\BaseRgsException;
use DocDoc\RgsApiClient\Exception\InternalErrorRgsException;
use DocDoc\RgsApiClient\Exception\NotFoundRgsException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Абстрактный Клиент для работы с API РГС
 * Содержит в себе отправку, валидацию ответов, логирование запросов.
 *
 * @see https://chronicmonitor.docs.apiary.io/#reference/
 */
abstract class AbstractRgsClient
{
	/** @var ClientInterface */
	private $client;

	/** @var RgsApiParamsInterface */
	protected $apiParams;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * DocDocJsonRpcApi constructor.
	 *
	 * @param ClientInterface       $client
	 * @param RgsApiParamsInterface $apiParams
	 * @param LoggerInterface       $logger
	 */
	public function __construct(
		ClientInterface $client,
		RgsApiParamsInterface $apiParams,
		LoggerInterface $logger
	)
	{
		$this->client = $client;
		$this->apiParams = $apiParams;
		$this->logger = $logger;
	}

	/**
	 * @param RequestInterface $request
	 *
	 * @return ResponseInterface
	 * @throws BadRequestRgsException
	 * @throws BaseRgsException
	 */
	protected function send(RequestInterface $request): ResponseInterface
	{
		$response = null;
		try {
			$response = $this->client->send($request);

			$this->logger->info(
				'Отправлен запрос партнеру РГС',
				[
					'partnerId' => $this->apiParams->getPartnerId(),
					'url' => $request->getUri(),
					'request' => $request
				]
			);
			$this->validate($response);
		} catch (GuzzleException $e) {
			$this->logger->error(
				'Критическая Ошибка при запросе к партнёру РГС',
				[
					'partnerId' => $this->apiParams->getPartnerId(),
					'url' => $request->getUri(),
					'request' => $request,
					'exception' => $e,
				]
			);
		} catch (BaseRgsException $e) {
			$this->logger->error(
				$e->getMessage(),
				[
					'partnerId' => $this->apiParams->getPartnerId(),
					'url' => $request->getUri(),
					'request' => $request,
					'responseBody' => $response ? $response->getBody()->getContents() : null,
					'exception' => $e,
				]
			);
			throw $e;
		}
		return $response;
	}

	/**
	 * Валидация ответа
	 *
	 * Специфика json Rpc в том что он всегда возвращает 200.
	 *
	 * @param ResponseInterface|null $response
	 *
	 * @throws BadRequestRgsException
	 * @throws InternalErrorRgsException
	 * @throws NotFoundRgsException
	 */
	private function validate(?ResponseInterface $response): void
	{
		if ($response === null) {
			throw new BadRequestRgsException('РГС сервис не прислал ответ.');
		}
		switch ($response->getStatusCode()) {
			case 200:
			case 201:
				break;
			case 400:
				throw new InternalErrorRgsException($response);
			case 404:
				throw new NotFoundRgsException($response);
			default:
				throw new BadRequestRgsException($response);
		}
	}

	/**
	 * Сборка объекта запроса
	 *
	 * @param string $method
	 * @param string $url
	 * @param string $body
	 *
	 * @return Request
	 */
	protected function buildRequest(string $method, string $url, string $body): Request
	{
		return new Request(
			$method,
			rtrim($this->apiParams->getHost(), '/') . '/' . ltrim($url, '/'),
			[],
			$body
		);
	}
}
