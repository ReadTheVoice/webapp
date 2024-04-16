<?php

namespace App\Helper;

class GitHelper
{
    private string $gitDir;

    public function __construct(string $projectDir)
    {
        $this->gitDir = $projectDir . '/.git';
    }

    public function getGitInformation(): array
    {
        $headContent = file_get_contents($this->gitDir . '/HEAD');
        $branch = null;
        $hash = null;
        $tag = null;

        if (preg_match('#ref: refs/heads/(.+)#', $headContent, $matches)) {
            $branch = $matches[1];
            $branchPath = $this->gitDir . sprintf('/refs/heads/%s', $branch);
            if (is_readable($branchPath) && $hashContent = file_get_contents($branchPath)) {
                $hash = substr($hashContent, 0, 7);
            }

            $tagPath = $this->gitDir . sprintf('/refs/tags/%s', $branch);
            if (is_readable($tagPath) && $tagContent = file_get_contents($tagPath)) {
                $tag = substr($tagContent, 0, 7);
            }
        }

        return ['branch' => $branch, 'hash' => $hash, 'tag' => $tag];
    }
}