<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
putenv('DEBUG=');

@include_once('../vendor/autoload.php');

$searches = [
    ['title' => 'lenovo \bp2\b', 'max_price' => 8000, 'min_price' => 5000, 'ignore_area' => TRUE],
    ['title' => 'qc35', 'max_price' => 15000, 'min_price' => 3000, 'ignore_area' => TRUE],
    ['title' => 'ift', 'max_price' => 100000, 'min_price' => 0, 'ignore_area' => TRUE],
];

$areas = ['amanora', 'magarpatta', 'hadapsar', 'kothrud'];

$driver = new \Behat\Mink\Driver\GoutteDriver();
$driver->getClient()->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1');
$session = new \Behat\Mink\Session($driver);

$session->start();

$html = '';
$seen = json_decode(cache_online('olx'), TRUE) ?: [];

foreach ($searches as $search) {
    $slug = slugify(preg_replace('/(\\\\b)/', '', $search['title']));
    $session->visit("https://www.olx.in/pune/q-$slug/?search%5Bphotos%5D=false");
    $page = $session->getPage();
    $tables = $page->findAll('css', 'table[summary="Ad"]');

    /** @var \Behat\Mink\Element\Element $table */
    foreach ($tables as $table) {
        try {
            $ad = $table->find('css', 'h3 > a');
            $title = $ad->getText();
            $link = trim(preg_replace('/\#.*/', '', $ad->getAttribute('href')));

            if (empty($seen[$link])) {
                $seen[$link] = TRUE;

                $price = $table->find('css', 'p.price')->getText();
                $loc = $table->find('css', 'p.margintop3 > small > span')->getText();
                $words = preg_split('/ +/', $search['title']);
                $pass = empty($price) || ($price <= ($search['max_price'] ?? 100000));
                $pass = $pass && (empty($price) || ($price >= ($search['min_price'] ?? 0)));

                foreach ((array) $words as $word) {
                    $pass = $pass && preg_match("/$word/i", $title);
                }

                if (!empty($pass)) {
                    if (preg_match('/pune/i', $loc) || !empty($search['ignore_area'])) {
                        foreach ($areas as $area) {
                            if (preg_match("/$area/i", $loc) || !empty($search['ignore_area'])) {
                                $html .= "$title - $price - $loc\n$link\n\n";
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }
}

if (!empty($html)) {
    print $html;
    email('sanchit.notify@gmail.com', 'sanchitbh@gmail.com', 'olx items', $html);

    if (!empty($seen)) {
        cache_online('olx', json_encode($seen));
    }
}