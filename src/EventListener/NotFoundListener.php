<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof NotFoundHttpException)
        {
            $response = new RedirectResponse('/?404');

            $event->setResponse($response);
        }
    }
}