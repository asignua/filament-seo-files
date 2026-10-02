<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\Support\AtomicFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * The atomic write must never do worse than the plain `File::put` of v1.0.0: a hardened
 * deploy gives php-fpm write access to robots.txt / llms.txt, not to `public/`.
 */
class AtomicFileTest extends TestCase
{
    public function test_it_writes_a_new_file_and_leaves_no_temporary_file(): void
    {
        AtomicFile::put($this->publicPath.'/robots.txt', "A\n");

        $this->assertSame("A\n", file_get_contents($this->publicPath.'/robots.txt'));
        $this->assertSame(['robots.txt'], array_values(array_diff(scandir($this->publicPath) ?: [], ['.', '..'])));
    }

    public function test_it_keeps_the_mode_of_an_existing_file(): void
    {
        $path = $this->publicPath.'/robots.txt';
        file_put_contents($path, 'old');
        chmod($path, 0o664);

        AtomicFile::put($path, 'new');

        clearstatcache(true, $path);
        $this->assertSame('new', file_get_contents($path));
        $this->assertSame(0o664, fileperms($path) & 0o777);
    }

    public function test_it_writes_in_place_when_the_temporary_file_cannot_be_created(): void
    {
        // A writable file in a directory where nothing can be created. Root ignores
        // directory permissions, so /proc is the portable stand-in: /proc/self/comm is
        // writable, a new file next to it is not.
        $path = '/proc/self/comm';

        if (!is_file($path) || !is_writable($path)) {
            $this->markTestSkipped('Needs Linux /proc.');
        }

        $original = (string) file_get_contents($path);

        try {
            AtomicFile::put($path, 'seofilestest');

            $this->assertSame('seofilestest', trim((string) file_get_contents($path)));
        } finally {
            file_put_contents($path, trim($original));
        }
    }

    public function test_someone_elses_file_is_written_in_place_and_keeps_its_owner(): void
    {
        if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Changing a file owner needs root.');
        }

        $path = $this->publicPath.'/llms.txt';
        file_put_contents($path, 'old');
        chown($path, 4321);

        AtomicFile::put($path, 'new');

        clearstatcache(true, $path);
        $this->assertSame('new', file_get_contents($path));
        $this->assertSame(4321, fileowner($path));
    }
}
