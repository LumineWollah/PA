<?php 

namespace App\EventListener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UniqueConstraintViolationListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof UniqueConstraintViolationException) {
            // Custom status code and message
            $response = new JsonResponse([
                'status' => 409, // Conflict status code
                'message' => 'This email is already registered.',
            ], 409);

            $event->setResponse($response);
        }
    }
}
