<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Override connectionsToTransact to disable transactions for in-memory SQLite
     * to avoid "cannot start a transaction within a transaction" errors in PHP 8.4+
     */
    protected function connectionsToTransact()
    {
        // Check if we're using in-memory SQLite
        $defaultConnection = config('database.default');
        $databasePath = config("database.connections.{$defaultConnection}.database");
        
        if ($databasePath === ':memory:') {
            // Return empty array to disable transactions for in-memory databases
            return [];
        }
        
        // For other databases, use the default behavior
        return property_exists($this, 'connectionsToTransact')
            ? $this->connectionsToTransact
            : [$defaultConnection];
    }

    /**
     * Clean up after each test to prevent database locks
     */
    protected function tearDown(): void
    {
        // Clear any Mockery mocks first
        if (class_exists('\Mockery')) {
            \Mockery::close();
        }
        
        // Close all database connections to prevent locks
        // Only disconnect if we're using file-based SQLite
        $defaultConnection = config('database.default');
        $databasePath = config("database.connections.{$defaultConnection}.database");
        
        if ($defaultConnection === 'sqlite' && $databasePath !== ':memory:') {
            try {
                DB::disconnect();
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
        
        parent::tearDown();
    }
}
