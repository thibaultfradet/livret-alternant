<?php

namespace App\EventSubscriber;

use App\Entity\ErrorLog;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExceptionLogSubscriber implements EventSubscriberInterface
{
    private const SENSITIVE_KEYS = [
        'password', 'plainPassword', '_password', 'token',
        'repeat_password', 'new_password', 'current_password',
    ];

    private bool $alreadyLogging = false;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 0]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->alreadyLogging || !$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        if ($statusCode !== 500) {
            return;
        }

        $request = $event->getRequest();

        $payload = null;
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $content = $request->getContent();
            $contentType = $request->headers->get('Content-Type', '');

            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($content, true);
                $payload = is_array($decoded) ? $decoded : null;
            } else {
                $payload = $request->request->all() ?: null;
            }

            if ($payload !== null) {
                $payload = $this->stripSensitiveKeys($payload);
            }
        }

        $userId = null;
        $userEmail = null;
        $userRoles = null;
        $token = $this->tokenStorage->getToken();
        if ($token !== null) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $userId = $user->getId();
                $userEmail = $user->getEmail();
                $userRoles = $user->getRoles();
            }
        }

        $this->alreadyLogging = true;
        try {
            $em = $this->registry->getManagerForClass(ErrorLog::class);
            if ($em === null) {
                return;
            }

            $log = (new ErrorLog())
                ->setHttpMethod($request->getMethod())
                ->setUrl($request->getUri())
                ->setRequestPayload($payload)
                ->setErrorMessage($exception->getMessage())
                ->setStackTrace($exception->getTraceAsString())
                ->setStatusCode($statusCode)
                ->setUserId($userId)
                ->setUserEmail($userEmail)
                ->setUserRoles($userRoles);

            $em->persist($log);
            $em->flush();
        } catch (\Throwable) {
            // Absorb silently — logging must never cause a secondary exception
        } finally {
            $this->alreadyLogging = false;
        }
    }

    private function stripSensitiveKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->stripSensitiveKeys($value);
            }
        }

        return $data;
    }
}
