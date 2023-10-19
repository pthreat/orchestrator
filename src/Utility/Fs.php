<?php

declare(strict_types=1);

namespace Pthreat\Orchestrator\Utility;

class Fs
{
    public static function mkPath(...$params) : string
    {
        return implode(\DIRECTORY_SEPARATOR, $params);
    }

    public static function findFile(string $dir, string $find, $includeDirectories = false, $includeHiddenFiles = false) : array
    {
        $return = [];

        $d = new \DirectoryIterator($dir);
        $find = preg_quote($find, '#');

        foreach ($d as $file) {
            if (
                (false === $includeDirectories && $file->isDir()) ||
                (false === $includeHiddenFiles && $file->isDot()) ||
                !preg_match("#$find#", (string) $file)
            ) {
                continue;
            }

            $return[] = clone $file;
        }

        return $return;
    }

}
