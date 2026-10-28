<?php

namespace App\Helpers;

class Format
{
    public static function money(int|float|null $amount, string $currency = 'GNF'): string
    {
        if ($amount === null) return '—';
        return number_format((float) $amount, 0, ',', ' ') . ' ' . $currency;
    }

    public static function date(?string $dateStr, string $format = 'd MMMM yyyy'): string
    {
        if (!$dateStr) return '—';
        try {
            $ts = strtotime($dateStr);
            if ($ts === false) return '—';
            // Format français simplifié
            $months = [
                1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
            ];
            $d = (int) date('j', $ts);
            $m = (int) date('n', $ts);
            $y = date('Y', $ts);
            return $d . ' ' . $months[$m] . ' ' . $y;
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function dateTime(?string $dateStr): string
    {
        if (!$dateStr) return '—';
        $ts = strtotime($dateStr);
        if ($ts === false) return '—';
        $months = [
            1 => 'jan', 2 => 'fév', 3 => 'mar', 4 => 'avr',
            5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'aoû',
            9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'déc',
        ];
        $d = (int) date('j', $ts);
        $m = (int) date('n', $ts);
        $y = date('Y', $ts);
        $h = date('H:i', $ts);
        return $d . ' ' . $months[$m] . ' ' . $y . ' à ' . $h;
    }

    public static function dateShort(?string $dateStr): string
    {
        if (!$dateStr) return '—';
        $ts = strtotime($dateStr);
        return $ts !== false ? date('d/m/Y', $ts) : '—';
    }

    public static function monthDay(?string $dateStr): array
    {
        if (!$dateStr) return ['month' => '', 'day' => ''];
        $ts = strtotime($dateStr);
        if ($ts === false) return ['month' => '', 'day' => ''];
        $months = [
            1 => 'jan', 2 => 'fév', 3 => 'mar', 4 => 'avr',
            5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'aoû',
            9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'déc',
        ];
        return [
            'month' => $months[(int) date('n', $ts)],
            'day'   => date('j', $ts),
        ];
    }
}

const MODALITY_LABELS = [
    'presentiel' => 'Présentiel',
    'en_ligne'   => 'En ligne',
    'hybride'    => 'Hybride',
];

const ENROLLMENT_STATUS_LABELS = [
    'en_attente' => 'En attente',
    'valide'     => 'Validé',
    'refuse'     => 'Refusé',
    'annule'     => 'Annulé',
    'complete'   => 'Complété',
];
