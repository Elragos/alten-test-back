<?php

namespace Tests;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class TestData
{
    private string $configPath;
    private array $users = [];
    private array $products = [];

    public function __construct()
    {
        $this->configPath = __DIR__ . '/../config';
    }

    public function loadData(): void
    {
        $testDataPath = $this->configPath . '/initialData.json';
        if (file_exists($testDataPath)) {
            $testDataContent = file_get_contents($testDataPath);
            $testData = json_decode($testDataContent, true);
            $this->users = $testData["users"] ?? [];
            $this->products = $testData["products"] ?? [];
        } else {
            throw new FileNotFoundException("Test data file \"$testDataPath\" does not exist.");
        }
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getProducts(): array
    {
        return $this->products;
    }
}
