<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@include_once('../vendor/autoload.php');

$merchants = ['flipkart', 'amazon', 'paytm'];
$max_price = 10000;
$filters = [
    ['title' => 'whiteline'],
    ['title' => 'pampers', 'price' => 700],
    ['title' => 'wet wipe'],
    ['title' => 'bread maker'],
    ['title' => 'koss'],
    ['title' => 'klipsh'],
    ['title' => 'fuzzy cooker'],
    ['title' => 'vacuum'],
    ['title' => 'wifi extender'],
    ['title' => 'bosch screwdriver'],
    ['title' => 'vero moda'],
    ['title' => 'forever new'],
    ['title' => 'gant'],
    ['title' => 'louis philippe'],
    ['title' => 'duracell'],
    ['title' => 'meemee'],
    ['title' => 'jockey'],
    ['title' => 'loot'],
    ['title' => 'Bragg'],
];

$driver = new \Behat\Mink\Driver\GoutteDriver();
$session = new \Behat\Mink\Session($driver);

$session->start();
$session->visit("https://www.desidime.com/forums/hot-deals-online?type=new");
$page = $session->getPage();
$boxes = $page->findAll('css', 'div.l-deal-box-textview');

/** @var \Behat\Mink\Element\Element $box */
foreach ($boxes as $box) {
    $link = $box->find('css', '.l-deal-dsp > a')->getAttribute('href');
    $title = $box->find('css', '.l-deal-dsp')->getText();
    $merchant = $box->find('css', '.l-deal-store')->getText();
    $price = 0;

    if ($priceBox = $box->find('css', '.l-deal-price')) {
        $price = $priceBox->getText();
    }

    if (TRUE) {
        foreach ($filters as $filter) {
            $words = preg_split('/\s+/', $filter['title']);
            $pass = empty($filter['merchant']) || in_array(strtolower($merchant), $merchants);
            $pass = $pass && (empty($price) || empty($filter['price']) || ($price <= $filter['price']));

            foreach ((array) $words as $word) {
                $pass = $pass && preg_match("/$word/i", $title);
            }

            if ($pass) {
                $results[] = ['title' => $title, 'link' => "https://www.desidime.com/$link", 'price' => $price ?: 0, 'merchant' => $merchant, 'keyword' => $filter['title']];
            }
        }
    }
}

$fn = __DIR__ . '/data/seen.php';
$seen = @include_once ($fn) ?: [];

if (!empty($results)) {
    $html = '';

    foreach ($results as $result) {
        if (empty($seen[$result['link']])) {
            $html .= sprintf("<h3>%s - %s</h3>\n<a href=\"%s\">%s</a>\n\n", $result['title'] . (!empty($result['merchant']) ? "(" . $result['merchant'] . ")" : ''), $result['price'], $result['link'], $result['link']);
            $items[] = $result['keyword'];
            $seen[$result['link']] = TRUE;
        }
    }

    if (!empty($html)) {
        print $html;
        //email('sanchitphone1@gmail.com', 'sanchitbh@gmail.com', 'desidime: ' . join(', ', $items), $html);
        if (!file_put_contents($fn, '<?' . 'php return ' . var_export($seen, TRUE) . ';')) {
            print "write failed :(";
        }
    }
}