<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

final class RankService
{
    public function proceduralNameFromIndex(int $index): string
    {
        $index = max(0, $index);

        $colors = [
            'Crimson',
            'Azure',
            'Emerald',
            'Amber',
            'Ivory',
            'Onyx',
            'Silver',
            'Golden',
            'Scarlet',
            'Teal',
            'Violet',
            'Copper',
        ];

        $adjectives = [
            'Brave',
            'Swift',
            'Wise',
            'Fierce',
            'Nimble',
            'Radiant',
            'Stalwart',
            'Mighty',
            'Bold',
            'Clever',
            'Steady',
            'Valiant',
        ];

        $animals = [
            'Lion',
            'Falcon',
            'Wolf',
            'Bear',
            'Otter',
            'Fox',
            'Eagle',
            'Tiger',
            'Panther',
            'Stag',
            'Dolphin',
            'Raven',
        ];

        $colorCount = count($colors);
        $adjectiveCount = count($adjectives);

        $color = $colors[$index % $colorCount];
        $adjective = $adjectives[intdiv($index, $colorCount) % $adjectiveCount];
        $animal = $animals[intdiv($index, $colorCount * $adjectiveCount) % count($animals)];

        return $adjective . ' ' . $color . ' ' . $animal;
    }

    public function rankFromScore(int $score): array
    {
        $rank = intdiv(max(0, $score), 20) + 1;

        $rankNames = [
            1 => 'Novice',
            2 => 'Apprentice',
            3 => 'Journeyman',
            4 => 'Master',
            5 => 'Grandmaster',
        ];

        $name = $rankNames[$rank] ?? 'Legend';
        $from = ($rank - 1) * 20;
        $to = $rank >= 6 ? null : ($from + 19);

        return [
            'rank' => $rank,
            'name' => $name,
            'from' => $from,
            'to' => $to,
        ];
    }
}
