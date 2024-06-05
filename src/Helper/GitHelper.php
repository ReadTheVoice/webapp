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
        }
    
        $tag = $this->getLastTag();
    
        return ['branch' => $branch, 'hash' => $hash, 'tag' => $tag];
    }
    
    private function getLastTag(): ?string
    {
        $tagsDir = $this->gitDir . '/refs/tags';
        $tags = scandir($tagsDir);
    
        if ($tags === false) {
            return null;
        }
    
        $tags = array_diff($tags, ['.', '..']);
        if (empty($tags)) {
            return null;
        }
    
        rsort($tags);
    
        return $tags[0];
    }
}