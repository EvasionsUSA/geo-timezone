<?php

declare(strict_types=1);

namespace Tochka\GeoTimeZone\Quadrant;

/**
 * @internal
 */
readonly class QuadrantBuilder
{
    public const INDEX_A = 'a';
    public const INDEX_B = 'b';
    public const INDEX_C = 'c';
    public const INDEX_D = 'd';

    public function getQuadrantById(Quadrant $quadrant, string $index): Quadrant
    {
        $centerX = $quadrant->topLeftX + ($quadrant->bottomRightX - $quadrant->topLeftX) / 2;
        $centerY = $quadrant->topLeftY + ($quadrant->bottomRightY - $quadrant->topLeftY) / 2;

        return match ($index) {
            self::INDEX_A => new Quadrant($quadrant->topLeftX, $quadrant->topLeftY, $centerX, $centerY, ($quadrant->id ?? '') . self::INDEX_A),
            self::INDEX_B => new Quadrant($centerX, $quadrant->topLeftY, $quadrant->bottomRightX, $centerY, ($quadrant->id ?? '') . self::INDEX_B),
            self::INDEX_C => new Quadrant($quadrant->topLeftX, $centerY, $centerX, $quadrant->bottomRightY, ($quadrant->id ?? '') . self::INDEX_C),
            self::INDEX_D => new Quadrant($centerX, $centerY, $quadrant->bottomRightX, $quadrant->bottomRightY, ($quadrant->id ?? '') . self::INDEX_D),
        };
    }

    /**
     * @param int $level
     * @param Quadrant|null $quadrant
     * @return list<Quadrant>
     */
    public function getQuadrantsByLevel(int $level, ?Quadrant $quadrant = null): array
    {
        if ($quadrant === null) {
            $quadrant = $this->getDefaultQuadrant();
        }
        
        if ($level === 0) {
            return [$quadrant];
        }

        $result = [];
        
        $quadrants = [
            $this->getQuadrantById($quadrant, QuadrantBuilder::INDEX_A),
            $this->getQuadrantById($quadrant, QuadrantBuilder::INDEX_B),
            $this->getQuadrantById($quadrant, QuadrantBuilder::INDEX_C),
            $this->getQuadrantById($quadrant, QuadrantBuilder::INDEX_D),
        ];

        foreach ($quadrants as $subQuadrant) {
            $result[] = $this->getQuadrantsByLevel($level - 1, $subQuadrant);
        }

        return array_merge(...$result);
    }

    public function getDefaultQuadrant(): Quadrant
    {
        return new Quadrant(-180.0, 90.0, 180.0, -90.0);
    }

    public function getQuadrantIndexByPoint(float $latitude, float $longitude, int $level): ?string
    {
        $quadrant = $this->getQuadrantByPoint(
            $this->getDefaultQuadrant(),
            $latitude,
            $longitude,
            $level,
        );

        return $quadrant->id;
    }

    public function getQuadrantByPoint(Quadrant $quadrant, float $latitude, float $longitude, int $level): Quadrant
    {
        if ($level === 0) {
            return $quadrant;
        }

        $centerX = $quadrant->topLeftX + ($quadrant->bottomRightX - $quadrant->topLeftX) / 2;
        $centerY = $quadrant->topLeftY + ($quadrant->bottomRightY - $quadrant->topLeftY) / 2;

        if ($longitude <= $centerX) {
            if ($latitude >= $centerY) {
                $index = self::INDEX_A;
            } else {
                $index = self::INDEX_C;
            }
        } else {
            if ($latitude >= $centerY) {
                $index = self::INDEX_B;
            } else {
                $index = self::INDEX_D;
            }
        }

        return $this->getQuadrantByPoint(
            $this->getQuadrantById($quadrant, $index),
            $latitude,
            $longitude,
            $level - 1,
        );
    }
}
