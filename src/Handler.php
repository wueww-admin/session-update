<?php


namespace SessionUpdate;


use Doctrine\DBAL\DBALException;
use SessionUpdate\DTO\Session;
use SessionUpdate\Exception\UserDataParseException;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\Http\HttpResponse;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestHandlerInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationRequestInterface;
use TopicAdvisor\Lambda\RuntimeApi\InvocationResponseInterface;

class Handler implements InvocationRequestHandlerInterface
{
    /**
     * @var SessionRepository
     */
    private $sessionRepository;

    public function __construct(SessionRepository $sessionRepository)
    {
        $this->sessionRepository = $sessionRepository;
    }

    /**
     * @param InvocationRequestInterface $request
     * @return bool
     */
    public function canHandle(InvocationRequestInterface $request): bool
    {
        return $request instanceof HttpRequestInterface;
    }

    /**
     * @param InvocationRequestInterface $request
     * @return void
     */
    public function preHandle(InvocationRequestInterface $request)
    {
    }

    /**
     * @param InvocationRequestInterface $request
     * @return InvocationResponseInterface
     * @throws DBALException
     * @throws \Exception
     */
    public function handle(InvocationRequestInterface $request): InvocationResponseInterface
    {
        if (!$request instanceof HttpRequestInterface) {
            throw new \LogicException('Must be invoked with HttpRequestInterface only');
        }

        $response = new HttpResponse($request->getInvocationId());

        if ($request->getMethod() !== 'PATCH') {
            $response->setStatusCode(405);
            return $response;
        }

        $sessionOld = $this->sessionRepository->findById((int) trim($request->getUri()->getPath(), '/'));

        if ($sessionOld === null) {
            $response->setStatusCode(404);
            return $response;
        }

        try {
            $sessionNew = Session::fromUserData(\json_decode($request->getBody(), true));
        } catch (UserDataParseException $ex) {
            $response->setStatusCode(400);
            $response->setBody($ex->getMessage());
            return $response;
        }

        $this->sessionRepository->update($sessionOld, $sessionNew);

        $response->setStatusCode(200);
        return $response;
    }

    /**
     * @param InvocationRequestInterface $request
     * @param InvocationResponseInterface $response
     * @return void
     */
    public function postHandle(InvocationRequestInterface $request, InvocationResponseInterface $response)
    {
    }
}
