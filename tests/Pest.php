<?php declare(strict_types=1);

function makeProject(array $files): string
{
    $root = sys_get_temp_dir() .'/rush-project-'. bin2hex(random_bytes(6));

    mkdir($root, 0700, true);

    foreach ($files as $path => $contents) {
        $file = $root .'/'. $path;
        $directory = dirname($file);

        if ( ! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        file_put_contents($file, $contents);
    }

    return $root;
}
