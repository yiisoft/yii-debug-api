<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use GitElephant\Objects\Commit;
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
            'sha' => $branch->getSha(),
            'remotes' => array_map(fn (Remote $repo) => [
                'name' => $repo->getName(),
                'branches' => array_keys($repo->getBranches()),
                'url' => $repo->getFetchURL(),
            ], $git->getRemotes(false)),
            'branches' => $git->getBranches(true),
            'lastCommit' => $this->serializeCommit($branch->getLastCommit()),
            'status' => $git->getStatusOutput(),
        ];
        $response = VarDumper::create($result)->asJson(false, 255);
        return $this->responseFactory->createResponse(json_decode($response, null, 512, JSON_THROW_ON_ERROR));
    }

    public function log(): ResponseInterface
    {
        $git = $this->getGit();

        $branch = $git->getMainBranch();
        $result = [
            'currentBranch' => $branch->getName(),
            'sha' => $branch->getSha(),
            'commits' => array_map($this->serializeCommit(...), $git->getLog(limit: 20)->toArray()),
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

    public function command(ServerRequestInterface $request): ResponseInterface
    {
        $git = $this->getGit();
        $availableCommands = ['pull', 'fetch'];

        $command = $request->getQueryParams()['command'] ?? null;

        if ($command === null) {
            throw new InvalidArgumentException('Command should not be empty.');
        }
        if (!in_array($command, $availableCommands, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown command "%s". Available commands: "%s".',
                    $command,
                    implode('", "', $availableCommands),
                )
            );
        }

        if ($command === 'pull') {
            $git->pull(rebase: false);
        } elseif ($command === 'fetch') {
            $git->fetch(tags: true);
        }
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

    private function serializeCommit(?Commit $commit): array
    {
        return $commit === null ? [] : [
            'sha' => $commit->getSha(true),
            'message' => $commit->getMessage()->getShortMessage(),
            'author' => [
                'name' => $commit->getAuthor()->getName(),
                'email' => $commit->getAuthor()->getEmail(),
            ],
        ];
    }
}
