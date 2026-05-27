<?php

namespace Tests\Unit;

use App\Services\PublicStorageSyncService;
use PHPUnit\Framework\TestCase;

class PublicStorageSyncServiceTest extends TestCase
{
    public function test_copies_missing_file_to_target_root(): void
    {
        $sourceRoot = sys_get_temp_dir().'/aoi-sync-source-'.uniqid();
        $targetRoot = sys_get_temp_dir().'/aoi-sync-target-'.uniqid();
        mkdir($sourceRoot.'/campaigns/9/assets', 0755, true);

        $relative = 'campaigns/9/assets/still-1.webp';
        file_put_contents($sourceRoot.'/'.$relative, 'fake-image');

        $service = new class($sourceRoot, $targetRoot) extends PublicStorageSyncService
        {
            public function __construct(
                protected string $testSource,
                protected string $testTarget,
            ) {}

            public function sourceRoot(): string
            {
                return $this->testSource;
            }

            public function targetRoot(): ?string
            {
                return $this->testTarget;
            }
        };

        $this->assertTrue($service->isSyncRequired());
        $this->assertTrue($service->syncRelativePath($relative));
        $this->assertFileExists($targetRoot.'/'.$relative);

        $this->assertFalse($service->syncRelativePath($relative));

        @unlink($targetRoot.'/'.$relative);
        @unlink($sourceRoot.'/'.$relative);
        @rmdir(dirname($sourceRoot.'/campaigns/9/assets/still-1.webp'));
        @rmdir(dirname($sourceRoot.'/campaigns/9/assets'));
        @rmdir(dirname($sourceRoot.'/campaigns/9'));
        @rmdir($sourceRoot.'/campaigns');
        @rmdir($sourceRoot);
        @rmdir($targetRoot.'/campaigns/9/assets');
        @rmdir($targetRoot.'/campaigns/9');
        @rmdir($targetRoot.'/campaigns');
        @rmdir($targetRoot);
    }
}
