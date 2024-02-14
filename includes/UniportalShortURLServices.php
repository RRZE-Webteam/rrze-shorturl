<?php

// 2021-02-14 BK : this is a translation in PHP 8.2 of "Services (ALT)" defined in https://github.com/RRZE-Webteam/rrze-shorturl/issues/2

namespace RRZE\ShortURL;

class UniportalShortURLServices {
    public static array $Services = [
        'blog' => [
            'title' => 'Artikel in einem Blog',
            'kurztitle' => 'Blogartikel',
            'servicestarturl' => 'http://blogs.fau.de',
            'prefix' => 7,
            'targeturl' => 'http://blogs.fau.de/go/$p1/$p2',
        ],
        'helpdesk' => [
            'title' => 'Helpdesk des RRZE',
            'kurztitle' => 'Helpdesk',
            'servicestarturl' => 'https://www.helpdesk.rrze.fau.de',
            'prefix' => 9,
            'targeturl' => 'https://www.helpdesk.rrze.fau.de/otrs/index.pl?Action=AgentZoom&TicketID=$id',
        ],
        'faq' => [
            'title' => 'Fragen und Antworten des RRZE',
            'kurztitle' => 'RRZE-FAQs',
            'servicestarturl' => 'https://www.faq.rrze.fau.de',
            'prefix' => 8,
            'targeturl' => 'https://www.helpdesk.rrze.fau.de/otrs/public.pl?Action=PublicFAQ&ItemID=$id',
        ],
        'wke' => [
            'title' => 'Webkongress Erlangen',
            'kurztitle' => 'Webkongressartikel',
            'servicestarturl' => 'http://webkongress.fau.de',
            'prefix' => 4,
            'targeturl' => 'http://webkongress.fau.de/?p=$p1',
        ],
        'ourdomains' => [
            'title' => 'Our Domains',
            'kurztitle' => 'Ourdomains',
            'servicestarturl' => '',
            'prefix' => 1,
            'targeturl' => '',
        ]
    ];
}

