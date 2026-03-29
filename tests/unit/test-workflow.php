<?php
/**
 * Unit tests for VAPTSECURE_Workflow class
 *
 * @package VAPT_Secure
 */

use PHPUnit\Framework\TestCase;

/**
 * Class Test_VAPTSECURE_Workflow
 *
 * Tests for workflow state transitions and status management.
 */
class Test_VAPTSECURE_Workflow extends TestCase
{
    /**
     * Test allowed transitions from draft status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_from_draft()
    {
        // Draft -> Develop should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('draft', 'develop'));
        
        // Draft -> Test should NOT be allowed
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('draft', 'test'));
        
        // Draft -> Release should NOT be allowed
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('draft', 'release'));
        
        // Draft -> Draft (same status) should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('draft', 'draft'));
    }

    /**
     * Test allowed transitions from develop status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_from_develop()
    {
        // Develop -> Draft should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('develop', 'draft'));
        
        // Develop -> Test should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('develop', 'test'));
        
        // Develop -> Release should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('develop', 'release'));
        
        // Develop -> Develop (same status) should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('develop', 'develop'));
    }

    /**
     * Test allowed transitions from test status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_from_test()
    {
        // Test -> Develop should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('test', 'develop'));
        
        // Test -> Release should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('test', 'release'));
        
        // Test -> Draft should NOT be allowed
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('test', 'draft'));
        
        // Test -> Test (same status) should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('test', 'test'));
    }

    /**
     * Test allowed transitions from release status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_from_release()
    {
        // Release -> Test should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('release', 'test'));
        
        // Release -> Develop should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('release', 'develop'));
        
        // Release -> Draft should be allowed (hard reset)
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('release', 'draft'));
        
        // Release -> Release (same status) should be allowed
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('release', 'release'));
    }

    /**
     * Test transition with legacy status names
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_with_legacy_status()
    {
        // Legacy 'available' -> 'in_progress' (maps to draft -> develop)
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('available', 'in_progress'));
        
        // Legacy 'in_progress' -> 'testing' (maps to develop -> test)
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('in_progress', 'testing'));
        
        // Legacy 'testing' -> 'implemented' (maps to test -> release)
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('testing', 'implemented'));
        
        // Legacy 'implemented' -> 'testing' (maps to release -> test)
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('implemented', 'testing'));
    }

    /**
     * Test transition with mixed case status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_case_insensitive()
    {
        // Mixed case should work
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('Draft', 'Develop'));
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('DRAFT', 'DEVELOP'));
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('Draft', 'DEVELOP'));
        
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('Release', 'Draft'));
        $this->assertTrue(VAPTSECURE_Workflow::is_transition_allowed('RELEASE', 'DRAFT'));
    }

    /**
     * Test invalid transitions return false
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_invalid_transitions()
    {
        // Invalid status combinations
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('draft', 'invalid'));
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('invalid', 'draft'));
        
        // Test -> Draft is not allowed
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('test', 'draft'));
    }

    /**
     * Test transition with unknown/undefined status
     *
     * @covers VAPTSECURE_Workflow::is_transition_allowed
     */
    public function test_is_transition_allowed_unknown_status()
    {
        // Unknown status passed through should return false for most transitions
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('unknown', 'draft'));
        $this->assertFalse(VAPTSECURE_Workflow::is_transition_allowed('draft', 'unknown'));
    }
}
