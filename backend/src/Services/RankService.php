<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

final class RankService
{
    private const ADJECTIVES = [
        'Agile', 'Alert', 'Ancient', 'Arcane', 'Astonishing', 'Astute', 'Bold', 'Brave',
        'Bright', 'Brisk', 'Calm', 'Candid', 'Clever', 'Cosmic', 'Crafty', 'Crimson',
        'Daring', 'Dauntless', 'Dazzling', 'Deep', 'Defiant', 'Eager', 'Earnest',
        'Electric', 'Elegant', 'Epic', 'Fabled', 'Fearless', 'Fierce', 'Gallant',
        'Gentle', 'Glorious', 'Golden', 'Grand', 'Grim', 'Hardy', 'Heroic', 'Honest',
        'Humble', 'Infinite', 'Intrepid', 'Iron', 'Jolly', 'Keen', 'Lively', 'Loyal',
        'Lucky', 'Majestic', 'Mighty', 'Nimble', 'Noble', 'Radiant', 'Rapid', 'Resolute',
        'Rugged', 'Savvy', 'Serene', 'Sharp', 'Silent', 'Sincere', 'Solid', 'Spirited',
        'Stalwart', 'Steady', 'Stellar', 'Stoic', 'Stormy', 'Strong', 'Swift', 'Tactful',
        'Tenacious', 'Thunderous', 'True', 'Unbroken', 'Valiant', 'Vast', 'Vigilant',
        'Vivid', 'Wild', 'Wise', 'Witty', 'Zealous', 'Zesty',
    ];

    private const COLORS = [
        'Amber', 'Amethyst', 'Apricot', 'Aqua', 'Azure', 'Beige', 'Black', 'Blue',
        'Bronze', 'Brown', 'Burgundy', 'Cerulean', 'Charcoal', 'Cobalt', 'Copper',
        'Coral', 'Crimson', 'Cyan', 'Ebony', 'Emerald', 'Fuchsia', 'Gold', 'Gray',
        'Green', 'Indigo', 'Ivory', 'Jade', 'Lavender', 'Lilac', 'Lime', 'Magenta',
        'Maroon', 'Mauve', 'Mint', 'Navy', 'Ochre', 'Olive', 'Onyx', 'Orange', 'Pearl',
        'Periwinkle', 'Pink', 'Platinum', 'Plum', 'Rose', 'Ruby', 'Saffron', 'Salmon',
        'Sand', 'Sapphire', 'Scarlet', 'Silver', 'Slate', 'Steel', 'Tan', 'Teal',
        'Turquoise', 'Ultramarine', 'Umber', 'Vermilion', 'Violet', 'White', 'Wine',
        'Yellow',
    ];

    private const ANIMALS = [
        'Aardvark', 'Albatross', 'Alligator', 'Antelope', 'Armadillo', 'Badger', 'Bat',
        'Bear', 'Beaver', 'Bison', 'Boar', 'Buffalo', 'Camel', 'Caribou', 'Cheetah',
        'Cougar', 'Coyote', 'Crane', 'Crocodile', 'Crow', 'Deer', 'Dolphin', 'Eagle',
        'Falcon', 'Ferret', 'Fox', 'Gazelle', 'Giraffe', 'Goat', 'Gorilla', 'Hawk',
        'Heron', 'Horse', 'Hyena', 'Ibex', 'Jaguar', 'Jackal', 'Koala', 'Leopard',
        'Lion', 'Llama', 'Lynx', 'Mammoth', 'Marten', 'Moose', 'Narwhal', 'Ocelot',
        'Orca', 'Otter', 'Owl', 'Panther', 'Penguin', 'Phoenix', 'Puma', 'Raccoon',
        'Raven', 'Rhino', 'Salamander', 'Seal', 'Shark', 'Sparrow', 'Stag', 'Tiger',
        'Tortoise', 'Viper', 'Walrus', 'Wolf', 'Wolverine', 'Yak', 'Zebra',
    ];

    public function rankFromScore(int $score): array
    {
        $rank = intdiv(max(0, $score), 20) + 1;

        $name = $this->buildRankName($rank);
        $from = ($rank - 1) * 20;
        $to = $rank >= 6 ? null : ($from + 19);

        return [
            'rank' => $rank,
            'name' => $name,
        ];
    }

    private function buildRankName(int $rank): string
    {
        return sprintf(
            '%s %s %s',
            $this->pickPart(self::ADJECTIVES, $rank, 17),
            $this->pickPart(self::COLORS, $rank, 31),
            $this->pickPart(self::ANIMALS, $rank, 47)
        );
    }

    private function pickPart(array $parts, int $rank, int $salt): string
    {
        $count = count($parts);
        $index = (($rank * $salt) + $salt) % $count;

        return $parts[$index];
    }
}
