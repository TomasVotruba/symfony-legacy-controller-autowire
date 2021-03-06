<?php

declare(strict_types=1);

namespace TomasVotruba\SymfonyLegacyControllerAutowire\Controller\Serializer;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

trait ControllerSerializerTrait
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $data
     * @param string[] $headers
     * @param string[] $context
     */
    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if ($this->serializer) {
            $data = $this->serializer->serialize($data, 'json', $context);
        }

        return new JsonResponse($data, $status, $headers, true);
    }
}
