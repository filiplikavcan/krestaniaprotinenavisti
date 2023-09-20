<?php

namespace App\Service;

class ListService
{
    public function find(string $slug): ?array
    {
        foreach ($this->all() as $list) {
            if ($slug === $list['slug']) {
                return $list;
            }
        }

        return null;
    }

    public function default(): array
    {
        return current($this->all());
    }

    public function all(): array
    {
        return [
            [
                'intro'        => 'Výzva k voľbám do NRSR v roku 2023',
                'title'        => 'My, dolupodpísaní občania Slovenskej republiky,',
                'enabled'      => false,
                'slug'     => 'volby-do-nrsr-2023',
                'limit'        => 25,
                'perex'        => '',
                'body_template' => 'volbyDoNrsr2023.html.twig',
            ],
            [
                'intro'        => 'Výzva k voľbám do NRSR v roku 2020',
                'title'        => 'My, dolupodpísaní',
                'enabled'      => false,
                'slug'     => 'volby-do-nrsr-2020',
                'limit'        => 50,
                'perex'        => 'občania Slovenskej republiky hlásiaci sa ku kresťanskej viere, sme presvedčení, že kresťanstvo je náboženstvo šíriace pokoj, pravdu a úctu ku každému človeku.',
                'body_template' => 'volbyDoNrsr2020.html.twig',
            ]
        ];
    }
}
