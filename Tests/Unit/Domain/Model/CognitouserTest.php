<?php
namespace Toumoro\TmCognito\Tests\Unit\Domain\Model;

/***
 *
 * This file is part of the "Backend authentication with AWS Cognito" Extension for TYPO3 CMS by Toumoro.com.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Toumoro.com (Simon Ouellet)
 *
 ***/

/**
 * Test case.
 */
class CognitouserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \Toumoro\TmCognito\Domain\Model\Cognitouser
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \Toumoro\TmCognito\Domain\Model\Cognitouser();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function dummyTestToNotLeaveThisFileEmpty()
    {
        self::markTestIncomplete();
    }
}
