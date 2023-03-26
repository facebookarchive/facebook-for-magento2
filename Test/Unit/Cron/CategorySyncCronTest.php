<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Cron;

use \Facebook\BusinessExtension\Helper\FBEHelper;
use \Facebook\BusinessExtension\Cron\CategorySyncCron;
use Facebook\BusinessExtension\Model\Feed\CategoryCollection;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class CategorySyncCronTest extends \PHPUnit\Framework\TestCase
{

    protected $categorySyncCron;

    protected $fbeHelper;
    protected $categoryCollection;
    protected $systemConfig;
    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->categoryCollection = $this->createMock(CategoryCollection::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->categorySyncCron = new \Facebook\BusinessExtension\Cron\CategorySyncCron(
            $this->fbeHelper,
            $this->categoryCollection,
            $this->systemConfig
        );
    }

    /**
     * Test that the cron won't run when disabled by user
     *
     * @return void
     */
    public function testNCronDisabled()
    {
        $this->systemConfig->method('isActiveCatalogSync')->willReturn(false);

        $result = $this->categorySyncCron->execute();

        $this->assertFalse($result);
    }

    /**
     * Test that cron will run when enabled by user
     *
     * @return void
     */
    public function testNCronEnabled()
    {
        $this->systemConfig->method('isActiveCatalogSync')->willReturn(true);

        $result = $this->categorySyncCron->execute();

        $this->assertTrue($result);
    }
}
