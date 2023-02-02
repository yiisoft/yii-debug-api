<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use GitElephant\Objects\Remote;
use GitElephant\Repository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final class GitController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function summary(): ResponseInterface
    {
        $git = $this->getGit();

        $branch = $git->getMainBranch();
        $result = [
            'currentBranch' => $branch->getName(),
            'remotes' => array_map(fn (Remote $repo) => [
                'name' => $repo->getName(),
                'branches' => array_keys($repo->getBranches()),
                'url' => $repo->getFetchURL(),
            ], $git->getRemotes(false)),
            'branches' => $git->getBranches(true),
            'lastCommit' => [
                'ref' => $branch->getLastCommit()->getSha(true),
                'message' => $branch->getLastCommit()->getMessage()->getShortMessage(),
                'author' => [
                    'name' => $branch->getLastCommit()->getAuthor()->getName(),
                    'email' => $branch->getLastCommit()->getAuthor()->getEmail(),
                ],
            ],
            'status' => $git->getStatusOutput(),
        ];
        $response = VarDumper::create($result)->asJson(false, 255);
        return $this->responseFactory->createResponse(json_decode($response, null, 512, JSON_THROW_ON_ERROR));
    }

    public function checkout(ServerRequestInterface $request): ResponseInterface
    {
        $git = $this->getGit();

        $branch = $request->getParsedBody()['branch'] ?? null;

        if ($branch === null) {
            throw new InvalidArgumentException('Branch should not be empty.');
        }

        $git->checkout($branch);
        return $this->responseFactory->createResponse([]);
    }

    private function getGit(): Repository
    {
        $projectPath = $this->aliases->get('@root');

        while ($projectPath !== '/') {
            try {
                $git = new Repository($projectPath);
                $git->getStatus();
                return $git;
            } catch (Throwable) {
                $projectPath = dirname($projectPath);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Could find any repositories up from "%s" directory.',
                $projectPath,
            )
        );
    }

}
